<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ShiftHub Call</title>
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; background: #0f172a; color: #e2e8f0; font-family: Arial, sans-serif; }
        .wrap { display: flex; flex-direction: column; width: 100%; height: 100%; }
        .top { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #1e293b; }
        .status { font-size: 12px; color: #cbd5e1; }
        .btn { border: 0; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 12px; }
        .btn.end { background: #dc2626; color: #fff; }
        .btn.toggle { background: #334155; color: #fff; }
        .main { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 8px; }
        audio { width: 100%; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="status" id="status">Connecting...</div>
        <div style="display:flex;gap:8px;">
            <button class="btn toggle" id="muteBtn">Mute</button>
            <button class="btn end" id="endBtn">End</button>
        </div>
    </div>
    <div class="main">
        <audio id="remoteAudio" autoplay playsinline></audio>
        <audio id="localAudio" autoplay muted playsinline></audio>
    </div>
</div>

<script>
(function () {
    const room = @json($room);
    const params = new URLSearchParams(window.location.search);
    const role = params.get('role') || 'callee';
    const callId = params.get('call_id') || '';
    const clientId = `c_${Math.random().toString(36).slice(2)}_${Date.now()}`;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const statusEl = document.getElementById('status');
    const remoteAudio = document.getElementById('remoteAudio');
    const localAudio = document.getElementById('localAudio');
    const muteBtn = document.getElementById('muteBtn');
    const endBtn = document.getElementById('endBtn');

    let pc = null;
    let localStream = null;
    let polling = null;
    let afterId = 0;
    let isMuted = false;
    let offerSent = false;

    function setStatus(text) { statusEl.textContent = text; }

    async function sendSignal(type, payload) {
        await fetch(`/calls/${encodeURIComponent(room)}/signals`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                sender: clientId,
                type,
                payload: { call_id: callId, ...payload }
            }),
        });
    }

    async function pollSignals() {
        try {
            const res = await fetch(`/calls/${encodeURIComponent(room)}/signals?after_id=${afterId}`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            const rows = json.data || [];
            for (const row of rows) {
                afterId = Math.max(afterId, Number(row.id || 0));
                if (row.sender === clientId) continue;
                let payload = null;
                try { payload = row.payload ? JSON.parse(row.payload) : {}; } catch (_) { payload = {}; }
                await handleSignal(row.type, payload || {});
            }
        } catch (_) {}
    }

    async function handleSignal(type, payload) {
        if (payload.call_id && callId && payload.call_id !== callId) return;
        if (type === 'offer') {
            await pc.setRemoteDescription(new RTCSessionDescription(payload.sdp));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            await sendSignal('answer', { sdp: answer });
            setStatus('In call');
            return;
        }
        if (type === 'answer') {
            await pc.setRemoteDescription(new RTCSessionDescription(payload.sdp));
            setStatus('In call');
            return;
        }
        if (type === 'candidate' && payload.candidate) {
            try {
                await pc.addIceCandidate(new RTCIceCandidate(payload.candidate));
            } catch (_) {}
            return;
        }
        if (type === 'end') {
            setStatus('Call ended');
            cleanup(false);
        }
    }

    async function startCallerFlow() {
        if (offerSent) return;
        offerSent = true;
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        await sendSignal('offer', { sdp: offer });
        setStatus('Ringing...');
    }

    function cleanup(sendEnd = true) {
        if (polling) {
            clearInterval(polling);
            polling = null;
        }
        if (sendEnd) {
            sendSignal('end', {}).catch(() => {});
        }
        if (pc) {
            try { pc.close(); } catch (_) {}
            pc = null;
        }
        if (localStream) {
            for (const t of localStream.getTracks()) t.stop();
            localStream = null;
        }
    }

    async function init() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            localAudio.srcObject = localStream;
        } catch (_) {
            setStatus('Microphone permission denied');
            return;
        }

        pc = new RTCPeerConnection({ iceServers: [] });
        for (const track of localStream.getTracks()) {
            pc.addTrack(track, localStream);
        }
        pc.ontrack = (event) => {
            remoteAudio.srcObject = event.streams[0];
            setStatus('In call');
        };
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal('candidate', { candidate: event.candidate.toJSON() }).catch(() => {});
            }
        };

        polling = setInterval(pollSignals, 1200);
        await pollSignals();
        if (role === 'caller') {
            await startCallerFlow();
        } else {
            setStatus('Waiting for caller...');
        }
    }

    muteBtn.addEventListener('click', () => {
        if (!localStream) return;
        isMuted = !isMuted;
        localStream.getAudioTracks().forEach((t) => (t.enabled = !isMuted));
        muteBtn.textContent = isMuted ? 'Unmute' : 'Mute';
    });

    endBtn.addEventListener('click', () => {
        cleanup(true);
        setStatus('Call ended');
    });

    window.addEventListener('beforeunload', () => cleanup(true));
    init();
})();
</script>
</body>
</html>
