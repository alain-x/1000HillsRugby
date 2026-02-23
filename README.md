# 1000HillsRugby
 

## Donations (Pesapal)

### Pages/endpoints

- `donate.php` - Donation form (amount, donor details)
- `pesapal_init.php` - Creates a Pesapal order and redirects the donor to Pesapal checkout
- `pesapal_callback.php` - Pesapal redirect page after payment (shows payment status)
- `pesapal_ipn.php` - IPN listener (Pesapal sends status changes here)

### Donation form (what the donor must fill)

Open `donate.php` and fill:

- **Amount** (required)
  - Must be within the limits configured in `.env`.
- **Full name** (optional)
- **Email** (optional)
- **Phone** (optional)
- **Purpose** (optional)

Important:

- The donor must provide at least one contact:
  - **Email OR Phone**

After submitting the form, the donor will be redirected to Pesapal where they can pay using available methods (MTN, Airtel Money, Visa, Mastercard) depending on what is enabled on the Pesapal merchant account.

### Configuration

All keys are read from `.env` (never put keys in HTML/JS).

Required:

```env
PESAPAL_ENV=production
PESAPAL_CONSUMER_KEY=YOUR_KEY
PESAPAL_CONSUMER_SECRET=YOUR_SECRET
PESAPAL_IPN_ID=YOUR_NOTIFICATION_ID
PESAPAL_DEFAULT_CURRENCY=RWF
```

Optional donation limits:

```env
PESAPAL_DONATION_MIN=100
PESAPAL_DONATION_MAX=5000000
```

Database (to store donation/payment status):

```env
DB_HOST=localhost
DB_USER=...
DB_PASS=...
DB_NAME=...
```

If DB is configured, a table named `donations_pesapal` will be created automatically on first use.

### Pesapal dashboard setup (IPN)

In your Pesapal business dashboard, register an IPN URL:

- **Website Domain:** `www.1000hillsrugby.rw`
- **IPN Listener URL:** `https://www.1000hillsrugby.rw/pesapal_ipn.php`

Pesapal will return a **Notification ID**. Set it in `.env` as:

```env
PESAPAL_IPN_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

### Callback URL

Each donation request sends a callback URL to Pesapal:

- `https://www.1000hillsrugby.rw/pesapal_callback.php`

### Troubleshooting

- If you see **"Payment is not configured yet (missing IPN ID)"**
  - Add `PESAPAL_IPN_ID` from the Pesapal dashboard.

- If you see **"Please provide at least an email or phone number"**
  - Fill either the email field or phone field on the donation form.

- For debugging (temporary):
  - Set `APP_DEBUG=true` in `.env` to display a more detailed Pesapal error message.
