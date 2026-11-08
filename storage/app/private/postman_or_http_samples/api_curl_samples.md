# Katalog Contoh Request cURL & Webhook (QA Contract — API-036)

Panduan pengujian manual dan regresi integrasi API eksternal Kost Asri.

---

## 1. Google OAuth 2.0
Membuka alur otentikasi Google:
```bash
curl -i -X GET "http://localhost:8000/auth/google/redirect"
```

---

## 2. Midtrans Snap Token
Meminta snap token untuk pembayaran invoice (memerlukan cookie session login):
```bash
curl -i -X POST "http://localhost:8000/invoices/1/snap-token" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: {CSRF_TOKEN}" \
  -d '{"amount": 1500000}'
```

---

## 3. Midtrans Webhook Simulation
Mengirimkan notifikasi pembayaran settlement dari Midtrans:
```bash
# Hitung signature SHA-512:
# printf "INV-ASRI-202611-0001-17626104002001500000.00{MIDTRANS_SERVER_KEY}" | sha512sum

curl -i -X POST "http://localhost:8000/webhook/midtrans" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "INV-ASRI-202611-0001-1762610400",
    "status_code": "200",
    "gross_amount": "1500000.00",
    "signature_key": "{GENERATED_SHA512_HASH}",
    "transaction_status": "settlement",
    "payment_type": "bank_transfer",
    "fraud_status": "accept"
  }'
```

---

## 4. WhatsApp Fonnte Webhook Simulation
Mengirimkan pesan inbound dari penyewa ke Chatbot WA:
```bash
curl -i -X POST "http://localhost:8000/webhook/fonnte?token={FONNTE_WEBHOOK_SECRET}" \
  -H "Content-Type: application/json" \
  -d '{
    "sender": "081234567890",
    "message": "1",
    "name": "Budi Santoso",
    "isGroup": "false"
  }'
```

---

## 5. Download Dokumen PDF (Stream)
Mengunduh Nota Reservasi:
```bash
curl -i -X GET "http://localhost:8000/reservations/RSV-202611-0001/pdf" \
  -H "Cookie: kost_asri_session={SESSION_ID}" \
  -o "nota-reservasi.pdf"
```

Mengunduh Kwitansi Pembayaran:
```bash
curl -i -X GET "http://localhost:8000/receipts/1/pdf" \
  -H "Cookie: kost_asri_session={SESSION_ID}" \
  -o "kwitansi.pdf"
```

