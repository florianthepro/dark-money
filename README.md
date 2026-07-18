# Secure Money Transfer // Dark-Money Node

> Oldschool BTC-denominated value-share interface for **Dark-Money**.  
> Simple pages. No admin account. No private-key storage. No heavy frontend stack.

```txt
[ SECURE MONEY TRANSFER ]
MODE: production-safe / demo
UNIT: Dark-Money
VALUE: BTC / satoshis
FEE: 1 USD converted live to BTC
UI: black box, green text, simple navigation
```

---

## What This Is

**Secure Money Transfer** is a small PHP-based web interface that manages a shared digital value unit called **Dark-Money**.

Dark-Money is modeled as **one shared unit**. Every user owns a percentage share of that unit based on their BTC-denominated balance.

```txt
user share = user satoshi balance / total satoshi balance
```

The system is intentionally simple:

- users create accounts
- users secure accounts with password + TOTP MFA
- users connect a BTC wallet address
- users can record deposits
- users can create withdrawal instructions
- users can transfer value internally by User ID
- BTC-facing actions apply a fixed 1 USD fee converted live to BTC

---

## Project Style

This project uses an oldschool terminal-style interface:

- black background
- green monospace text
- simple bordered panels
- no bloated UI framework
- no heavy JavaScript
- no AI/SaaS-looking design language

The goal is a clean, direct interface that feels like a small DEFCON-style utility panel instead of a modern marketing dashboard.

---

## Modes

### Production-Safe Mode

Production-safe mode creates the normal website flow.

Features:

- multiple user accounts
- password login
- TOTP MFA
- User ID based account identity
- BTC wallet address per user
- internal User ID transfers
- BTC deposit reports
- BTC withdrawal instructions
- external confirmed deposit endpoint
- no admin account
- no private-key storage

Account switching in production-safe mode works through normal login:

```txt
logout / switch account -> login with another User ID + password + TOTP
```

### Demo Mode

Demo mode creates a test environment with two demo users.

Demo mode behavior:

- two demo users are generated automatically
- no password input required
- no MFA input required
- switch user directly from the demo bar
- normal interface stays the same
- one extra page appears: `btc-transfer`
- `btc-transfer` simulates incoming/outgoing BTC
- no real Bitcoin transfer is performed

Demo mode is meant to show the 1:1 user experience without needing real BTC movement.

---

## Generated Files

The installer creates the project files automatically.

```txt
index.php                  main website
config.php                 generated configuration
free.txt                   branding and project information file
event_ingest.php           external confirmed deposit endpoint
.htaccess                  Apache protection and headers
assets/style.css           oldschool interface styling
data/app.sqlite            SQLite storage
data/btc_transfer_events.json
data/setup.lock
data/.htaccess
```

In demo mode, the same website is used. Demo behavior is controlled by mode and generated demo accounts.

---

## Branding File

`free.txt` is not only a public text file. It also acts as a simple branding/content file.

It contains sections like:

```txt
[IDENTITY]
PROJECT_NAME=Secure Money Transfer
BRAND_TEXT=SECURE MONEY TRANSFER
CURRENCY_NAME=Dark-Money
CURRENCY_SYMBOL=DM
NETWORK_VALUE_UNIT=BTC
FEE_LABEL=1 USD BTC fee

[PAGE_TITLES]
TAB_TITLE=Secure Money Transfer | Dark-Money
META_TITLE=Secure Money Transfer | Dark-Money BTC Value Management
HOME_HEADLINE=DARK-MONEY NODE
HOME_SUBTITLE=One shared BTC-denominated value unit with live BTC fee conversion and user-to-user transfers.

[SEARCH_PREVIEW]
META_DESCRIPTION=...
GOOGLE_SNIPPET=...
```

The website reads selected branding values from this file for titles, metadata and main page copy.

---

## Fee Model

BTC-facing actions use a fixed fiat fee:

```txt
fee = 1 USD converted live to BTC
```

Examples:

```txt
deposit BTC  -> live 1 USD fee deducted from credited amount
withdraw BTC -> live 1 USD fee deducted from payout amount
user transfer -> no fee
```

The BTC/USD value is fetched live and cached briefly to reduce external requests.

---

## Value Model

Dark-Money is one shared unit.

Each user balance is stored in satoshis.

```txt
total value = sum of all user satoshi balances
user share  = user balance / total value
```

Example:

```txt
User A: 100,000 sats
User B: 100,000 sats
Total:  200,000 sats

User A share: 50%
User B share: 50%
```

---

## Security Notes

This project is designed around a safer BTC handling model.

It does **not**:

- store Bitcoin private keys
- broadcast Bitcoin transactions from the web server
- include an admin account
- credit production deposits directly from user input alone
- require unsafe JavaScript libraries

It does:

- hash passwords with PHP password hashing
- require TOTP MFA in production-safe mode
- use CSRF tokens for forms
- use session hardening settings
- protect the data directory with `.htaccess`
- write BTC-facing actions into event logs
- provide `event_ingest.php` for externally confirmed credits

---

## External Deposit Confirmation

Production-safe mode does not trust user-submitted deposit reports as confirmed funds.

Confirmed deposits can be credited by an external watcher or backend script through:

```txt
event_ingest.php
```

The ingest key is generated during setup and written into `free.txt`.

Expected POST fields:

```txt
key        external ingest key
user_id    target User ID
credit_sat confirmed satoshi amount
txid       optional BTC transaction ID
```

---

## Installation

Upload the final setup file to your PHP server.

Recommended filename:

```txt
setup.php
```

Open it in the browser and choose one mode:

```txt
install production-safe
install demo with 2 users
```

After installation:

```txt
DELETE setup.php
```

The installer creates a lock file:

```txt
data/setup.lock
```

---

## Requirements

```txt
PHP 8+
SQLite PDO extension
Apache recommended for .htaccess protection
Outbound HTTPS access for live BTC/USD price lookup
```

---

## Demo Flow

```txt
1. Install demo mode.
2. Open index.php.
3. Use the demo bar to switch between the two generated users.
4. Open dashboard.
5. Use btc-transfer to simulate incoming BTC.
6. Check updated balance and Dark-Money share.
7. Test internal User ID transfer between the two users.
8. Check history.
```

The demo mode does not perform real BTC transfers.

---

## Production-Safe Flow

```txt
1. Install production-safe mode.
2. Create a user account.
3. Save the generated User ID and TOTP secret.
4. Log in with User ID, password and TOTP.
5. Report deposits or create withdrawal instructions.
6. Use an external watcher/backend process to confirm real deposits through event_ingest.php.
7. Transfer value internally by User ID when needed.
```

---

## Repository Warning

Do not commit installed runtime data.

Recommended ignored paths:

```gitignore
config.php
data/
setup.php
```

If you publish this repository, publish the source/installer only. Do not publish generated secrets, demo credentials from a real setup, ingest keys or SQLite databases.

---

## License

No license is included by default. Add one before public distribution if needed.

---

## Status

```txt
BUILD: final setup version
ADMIN: none
BTC KEYS: never stored
MODE: production-safe / demo
UI: simple terminal panel
```
