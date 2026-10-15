"""
AirTracking server (FastAPI) - pola sama seperti AgriFlow.

  Alat IoT --MQTT (airtracking/<ID>/data)--> server --> Supabase (devices.last + water_logs)
  Alat IoT --HTTP POST /api/ingest---------^
  Dashboard membaca langsung dari Supabase (login + RLS).

Format JSON dari alat:
  {"ts":"2026-10-15T08:30:00Z","loc":"LOK-01","id":"AIR-01","key":"<credential>",
   "ph":7.1,"ec":420,"tds":210,"do":6.8,"temp":27.5,"turb":3.2}
"""
from __future__ import annotations
import json, logging
from datetime import datetime, timezone
from typing import Optional

import yaml
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from supabase import create_client

logging.basicConfig(level=logging.INFO)
log = logging.getLogger("airtracking")
CONFIG = yaml.safe_load(open("config.yaml"))
sb = create_client(CONFIG["supabase"]["url"], CONFIG["supabase"]["service_key"])

app = FastAPI(title="AirTracking Server")
app.add_middleware(CORSMiddleware, allow_origins=CONFIG.get("cors_origins", ["*"]),
                   allow_methods=["GET", "POST"], allow_headers=["*"])


class Reading(BaseModel):
    id: str
    key: str
    loc: str
    ts: Optional[str] = None
    ph: Optional[float] = None
    ec: Optional[float] = None
    tds: Optional[float] = None
    do: Optional[float] = None
    temp: Optional[float] = None
    turb: Optional[float] = None


def handle(p: Reading):
    """Validasi ID alat + credential, lalu simpan."""
    ok = (sb.table("device_keys").select("device_id")
          .eq("device_id", p.id).eq("credential", p.key).execute().data)
    if not ok:
        raise HTTPException(401, "ID alat/credential salah atau alat belum diregistrasi")
    now = datetime.now(timezone.utc).isoformat()
    vals = {k: getattr(p, k) for k in ("ph", "ec", "tds", "do", "temp", "turb")}
    sb.table("water_logs").insert({"device_id": p.id, "loc_code": p.loc,
                                   "created_at": p.ts or now, **vals}).execute()
    sb.table("devices").update({"last": {**vals, "ts": p.ts or now}, "last_seen": now}).eq("id", p.id).execute()
    log.info("[%s] %s", p.id, vals)


@app.post("/api/ingest")
async def ingest(p: Reading):
    handle(p)
    return {"ok": True}


@app.get("/api/health")
async def health():
    return {"ok": True}


# ---- MQTT (opsional) ----
if CONFIG.get("mqtt"):
    import paho.mqtt.client as mqtt
    M = CONFIG["mqtt"]

    def on_message(c, u, msg):
        try:
            handle(Reading(**json.loads(msg.payload)))
        except Exception as e:
            log.warning("pesan MQTT ditolak: %s", e)

    mc = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2)
    if M.get("username"):
        mc.username_pw_set(M["username"], M.get("password", ""))
    mc.on_connect = lambda c, *a: c.subscribe(M["topic"])
    mc.on_message = on_message
    mc.connect_async(M["host"], M.get("port", 1883))
    mc.loop_start()
