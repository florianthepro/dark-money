<?php
$installerTitle = 'Secure Money Transfer Setup';
$errors = [];
$done = false;

function smt_post($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function smt_valid_btc_address($value) {
    return (bool)preg_match('/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,90}$/', $value);
}

function smt_write_file($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content, LOCK_EX);
}

function smt_random_key($length = 48) {
    return bin2hex(random_bytes($length));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baseUrl = rtrim(smt_post('base_url'), '/');
    $feeAddress = smt_post('fee_address');
    $darkAddress = smt_post('dark_address');
    $adminPassword = smt_post('admin_password');
    $adminPassword2 = smt_post('admin_password2');

    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a valid base URL.';
    }
    if (!smt_valid_btc_address($feeAddress)) {
        $errors[] = 'Enter a valid Bitcoin fee address.';
    }
    if (!smt_valid_btc_address($darkAddress)) {
        $errors[] = 'Enter a valid Bitcoin Dark-Money address.';
    }
    if (strlen($adminPassword) < 12) {
        $errors[] = 'The admin password must contain at least 12 characters.';
    }
    if ($adminPassword !== $adminPassword2) {
        $errors[] = 'The admin passwords do not match.';
    }
    if (file_exists(__DIR__ . '/data/setup.lock')) {
        $errors[] = 'Setup is already locked. Remove data/setup.lock only if you intentionally want to reinstall.';
    }

    if (!$errors) {
        $appKey = smt_random_key(32);
        $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $createdAt = gmdate('c');

        $config = <<<'PHP'
<?php
return [
    'app_name' => 'Secure Money Transfer',
    'currency_name' => 'Dark-Money',
    'base_url' => '__BASE_URL__',
    'fee_address' => '__FEE_ADDRESS__',
    'dark_money_address' => '__DARK_ADDRESS__',
    'admin_user' => 'admin',
    'admin_password_hash' => '__ADMIN_HASH__',
    'app_key' => '__APP_KEY__',
    'created_at' => '__CREATED_AT__',
    'storage' => __DIR__ . '/data/app.sqlite',
    'activation_total_usd' => 11.00,
    'activation_value_usd' => 10.00,
    'exchange_fee_usd' => 1.00,
    'payment_mode' => 'manual-review',
    'allow_real_private_keys' => false,
];
PHP;
        $config = str_replace(
            ['__BASE_URL__', '__FEE_ADDRESS__', '__DARK_ADDRESS__', '__ADMIN_HASH__', '__APP_KEY__', '__CREATED_AT__'],
            [addslashes($baseUrl), addslashes($feeAddress), addslashes($darkAddress), addslashes($adminHash), $appKey, $createdAt],
            $config
        );

        $freeTxt = <<<'TXT'
Secure Money Transfer is a digital value management platform for Dark-Money. Dark-Money represents one shared digital value unit whose ownership is divided between activated users. Users can create an account, secure it with password and TOTP-based MFA, connect their Bitcoin wallet address, activate their account with a BTC payment, deposit value, withdraw value and transfer value internally to other users by User ID. The platform uses a clear dark interface, simple navigation and transparent value calculations. Payments and withdrawals are handled through a manual-review safety model and the website never stores Bitcoin private keys.
TXT;

        $htaccess = <<<'HTACCESS'
Options -Indexes
DirectoryIndex index.php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
</IfModule>
<IfModule mod_headers.c>
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
Header always set Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
</IfModule>
<FilesMatch "^(config\.php|setup\.php)$">
Require all denied
</FilesMatch>
HTACCESS;

        $dataHtaccess = "Require all denied\n";

        $style = <<<'CSS'
:root{--bg:#070911;--panel:#101522;--panel2:#151b2b;--text:#f4f7fb;--muted:#9ba8bd;--accent:#7c4dff;--accent2:#00d4ff;--bad:#ff5c7a;--good:#34d399;--line:#263247}*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:radial-gradient(circle at top left,#18213a 0,#070911 42%);color:var(--text);min-height:100vh}.wrap{max-width:1120px;margin:0 auto;padding:28px}.nav{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px}.brand{font-weight:800;letter-spacing:.4px}.brand span{color:var(--accent2)}.nav a,.btn{color:var(--text);text-decoration:none;background:linear-gradient(135deg,var(--accent),#4f46e5);border:0;border-radius:14px;padding:11px 16px;font-weight:700;cursor:pointer;display:inline-block}.nav .links{display:flex;gap:10px;flex-wrap:wrap}.card{background:linear-gradient(180deg,rgba(21,27,43,.94),rgba(16,21,34,.94));border:1px solid var(--line);box-shadow:0 20px 70px rgba(0,0,0,.35);border-radius:24px;padding:24px;margin-bottom:18px}.hero{padding:42px}.hero h1{font-size:44px;line-height:1.05;margin:0 0 14px}.muted{color:var(--muted)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}.field{display:flex;flex-direction:column;gap:7px;margin:12px 0}.field input,.field select{background:#090d17;color:var(--text);border:1px solid var(--line);border-radius:14px;padding:13px;font-size:15px}.notice{border-left:4px solid var(--accent2);padding:12px 14px;background:#0c1220;border-radius:12px}.bad{color:var(--bad)}.good{color:var(--good)}table{width:100%;border-collapse:collapse;overflow:hidden}td,th{border-bottom:1px solid var(--line);padding:12px;text-align:left}code{background:#090d17;border:1px solid var(--line);padding:3px 6px;border-radius:8px;word-break:break-all}.tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}.secondary{background:#121a2a;border:1px solid var(--line)}.right{text-align:right}.small{font-size:13px}.footer{margin-top:32px;color:var(--muted);font-size:13px}@media(max-width:720px){.hero h1{font-size:34px}.nav{align-items:flex-start;flex-direction:column}.wrap{padding:18px}}
CSS;

        $index = <<<'PHP'
<?php
$config = require __DIR__ . '/config.php';
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure', '1'); }
session_name('SMTSESSID');
session_start();

function db() {
    static $pdo;
    global $config;
    if (!$pdo) {
        if (!is_dir(__DIR__ . '/data')) { mkdir(__DIR__ . '/data', 0755, true); }
        $pdo = new PDO('sqlite:' . $config['storage']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,totp_secret TEXT NOT NULL,wallet_address TEXT NOT NULL,balance_usd REAL NOT NULL DEFAULT 0,active INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS transactions(id INTEGER PRIMARY KEY AUTOINCREMENT,type TEXT NOT NULL,status TEXT NOT NULL,from_user TEXT,to_user TEXT,amount_usd REAL NOT NULL DEFAULT 0,fee_usd REAL NOT NULL DEFAULT 0,details TEXT,created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts(id INTEGER PRIMARY KEY AUTOINCREMENT,ip TEXT NOT NULL,created_at INTEGER NOT NULL)');
    }
    return $pdo;
}

function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function now(){return gmdate('c');}
function csrf(){if(empty($_SESSION['csrf'])){$_SESSION['csrf']=bin2hex(random_bytes(32));}return $_SESSION['csrf'];}
function check_csrf(){if($_SERVER['REQUEST_METHOD']==='POST' && (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']))){http_response_code(400);exit('Invalid CSRF token.');}}
function post($k,$d=''){return trim((string)($_POST[$k] ?? $d));}
function money($n){return number_format((float)$n,2,'.',',');}
function btc_addr($v){return preg_match('/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,90}$/',$v);}
function user(){if(empty($_SESSION['uid']))return null;$st=db()->prepare('SELECT * FROM users WHERE user_id=?');$st->execute([$_SESSION['uid']]);return $st->fetch(PDO::FETCH_ASSOC) ?: null;}
function require_user(){if(!user()){header('Location:?p=login');exit;}}
function require_admin(){if(empty($_SESSION['admin'])){header('Location:?p=admin');exit;}}
function tx($type,$status,$from,$to,$amount,$fee,$details){$st=db()->prepare('INSERT INTO transactions(type,status,from_user,to_user,amount_usd,fee_usd,details,created_at) VALUES(?,?,?,?,?,?,?,?)');$st->execute([$type,$status,$from,$to,$amount,$fee,$details,now()]);}
function total_value(){return (float)db()->query('SELECT COALESCE(SUM(balance_usd),0) FROM users WHERE active=1')->fetchColumn();}
function share($balance){$t=total_value();return $t>0?($balance/$t*100):0;}

function b32_encode($data){$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';$out='';for($i=0;$i<strlen($data);$i++){$bits.=str_pad(decbin(ord($data[$i])),8,'0',STR_PAD_LEFT);}foreach(str_split($bits,5) as $chunk){if(strlen($chunk)<5)$chunk=str_pad($chunk,5,'0');$out.=$alphabet[bindec($chunk)];}return $out;}
function b32_decode($b32){$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$b32=strtoupper(preg_replace('/[^A-Z2-7]/','',$b32));$bits='';$out='';for($i=0;$i<strlen($b32);$i++){$v=strpos($alphabet,$b32[$i]);if($v!==false)$bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT);}foreach(str_split($bits,8) as $chunk){if(strlen($chunk)===8)$out.=chr(bindec($chunk));}return $out;}
function totp_secret(){return b32_encode(random_bytes(20));}
function totp_code($secret,$time=null){$time=$time ?? time();$counter=floor($time/30);$key=b32_decode($secret);$bin=pack('N*',0).pack('N*',$counter);$hash=hash_hmac('sha1',$bin,$key,true);$offset=ord(substr($hash,-1)) & 0x0F;$truncated=((ord($hash[$offset]) & 0x7F)<<24)|(ord($hash[$offset+1])<<16)|(ord($hash[$offset+2])<<8)|ord($hash[$offset+3]);return str_pad((string)($truncated % 1000000),6,'0',STR_PAD_LEFT);}
function verify_totp($secret,$code){$code=preg_replace('/\D/','',$code);for($i=-1;$i<=1;$i++){if(hash_equals(totp_code($secret,time()+($i*30)),$code))return true;}return false;}
function random_user_id(){return 'DM-'.strtoupper(bin2hex(random_bytes(4)));}
function can_login(){ $ip=$_SERVER['REMOTE_ADDR'] ?? 'x'; $cut=time()-900; $st=db()->prepare('DELETE FROM login_attempts WHERE created_at<?');$st->execute([$cut]); $st=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=?');$st->execute([$ip]); return ((int)$st->fetchColumn())<8;}
function log_fail(){ $ip=$_SERVER['REMOTE_ADDR'] ?? 'x'; $st=db()->prepare('INSERT INTO login_attempts(ip,created_at) VALUES(?,?)');$st->execute([$ip,time()]);}

check_csrf();
$p=$_GET['p'] ?? 'home';
$msg='';$err='';

if($p==='logout'){session_destroy();header('Location:?p=home');exit;}
if($p==='admin_logout'){unset($_SESSION['admin']);header('Location:?p=home');exit;}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if($p==='register'){
        $wallet=post('wallet');$pass=post('password');
        if(!btc_addr($wallet)){$err='Enter a valid Bitcoin wallet address.';}
        elseif(strlen($pass)<10){$err='Password must contain at least 10 characters.';}
        else{$uid=random_user_id();$secret=totp_secret();$st=db()->prepare('INSERT INTO users(user_id,password_hash,totp_secret,wallet_address,created_at) VALUES(?,?,?,?,?)');$st->execute([$uid,password_hash($pass,PASSWORD_DEFAULT),$secret,$wallet,now()]);$_SESSION['pending_uid']=$uid;$msg='Account created. Configure MFA before login.';header('Location:?p=mfa&uid='.urlencode($uid));exit;}
    }
    if($p==='login'){
        if(!can_login()){$err='Too many login attempts. Try again later.';}
        else{$uid=post('user_id');$st=db()->prepare('SELECT * FROM users WHERE user_id=?');$st->execute([$uid]);$u=$st->fetch(PDO::FETCH_ASSOC);if($u && password_verify(post('password'),$u['password_hash']) && verify_totp($u['totp_secret'],post('totp'))){$_SESSION['uid']=$u['user_id'];header('Location:?p=dashboard');exit;}else{log_fail();$err='Login failed.';}}
    }
    if($p==='admin'){
        global $config;
        if(post('admin_user')===$config['admin_user'] && password_verify(post('password'),$config['admin_password_hash'])){$_SESSION['admin']=1;header('Location:?p=admin_dashboard');exit;}else{$err='Admin login failed.';}
    }
    if($p==='activation_request'){require_user();$u=user();tx('activation','pending',$u['user_id'],null,$config['activation_value_usd'],$config['exchange_fee_usd'],'User reports an activation payment of 11 USD equivalent BTC. Manual review required.');$msg='Activation request submitted for manual review.';}
    if($p==='deposit'){require_user();$amount=(float)post('amount');$u=user();if($amount<=1){$err='Deposit must be more than the 1 USD fee.';}else{tx('deposit','pending',$u['user_id'],null,$amount-$config['exchange_fee_usd'],$config['exchange_fee_usd'],'Manual review deposit request. User stated total payment value: '.money($amount).' USD.');$msg='Deposit request submitted for manual review.';}}
    if($p==='withdraw'){require_user();$amount=(float)post('amount');$u=user();if($amount<=1 || $amount>$u['balance_usd']){$err='Invalid amount. It must be more than 1 USD and not exceed your balance.';}else{tx('withdrawal','pending',$u['user_id'],null,$amount-$config['exchange_fee_usd'],$config['exchange_fee_usd'],'Manual review payout to wallet '.$u['wallet_address']);$msg='Withdrawal request submitted for manual review.';}}
    if($p==='transfer'){require_user();$u=user();$to=post('to_user');$amount=(float)post('amount');$st=db()->prepare('SELECT * FROM users WHERE user_id=? AND active=1');$st->execute([$to]);$r=$st->fetch(PDO::FETCH_ASSOC);if(!$r){$err='Target User ID not found or not active.';}elseif($to===$u['user_id']){$err='You cannot transfer to yourself.';}elseif($amount<=0 || $amount>$u['balance_usd']){$err='Invalid transfer amount.';}else{$pdo=db();$pdo->beginTransaction();$pdo->prepare('UPDATE users SET balance_usd=balance_usd-? WHERE user_id=?')->execute([$amount,$u['user_id']]);$pdo->prepare('UPDATE users SET balance_usd=balance_usd+? WHERE user_id=?')->execute([$amount,$to]);tx('transfer','completed',$u['user_id'],$to,$amount,0,'Internal User ID transfer.');$pdo->commit();$msg='Transfer completed.';}}
    if($p==='settings'){require_user();$wallet=post('wallet');if(!btc_addr($wallet)){$err='Enter a valid Bitcoin wallet address.';}else{$u=user();db()->prepare('UPDATE users SET wallet_address=? WHERE user_id=?')->execute([$wallet,$u['user_id']]);$msg='Wallet address updated.';}}
    if($p==='admin_action'){require_admin();$id=(int)post('txid');$action=post('action');$st=db()->prepare('SELECT * FROM transactions WHERE id=?');$st->execute([$id]);$t=$st->fetch(PDO::FETCH_ASSOC);if($t && $t['status']==='pending'){$pdo=db();$pdo->beginTransaction();if($action==='approve'){if($t['type']==='activation'){$pdo->prepare('UPDATE users SET active=1,balance_usd=balance_usd+? WHERE user_id=?')->execute([$t['amount_usd'],$t['from_user']]);}elseif($t['type']==='deposit'){$pdo->prepare('UPDATE users SET balance_usd=balance_usd+? WHERE user_id=?')->execute([$t['amount_usd'],$t['from_user']]);}elseif($t['type']==='withdrawal'){$pdo->prepare('UPDATE users SET balance_usd=balance_usd-? WHERE user_id=? AND balance_usd>=?')->execute([$t['amount_usd']+$t['fee_usd'],$t['from_user'],$t['amount_usd']+$t['fee_usd']]);}$pdo->prepare('UPDATE transactions SET status="approved" WHERE id=?')->execute([$id]);}else{$pdo->prepare('UPDATE transactions SET status="rejected" WHERE id=?')->execute([$id]);}$pdo->commit();}header('Location:?p=admin_dashboard');exit;}
}

function header_html($title){global $config;echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="'.e(@file_get_contents(__DIR__.'/free.txt')).'"><title>'.e($title).' | '.e($config['app_name']).'</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="wrap"><div class="nav"><div class="brand">Secure <span>Money</span> Transfer</div><div class="links"><a class="secondary" href="?p=home">Home</a>';if(user()){echo '<a class="secondary" href="?p=dashboard">Dashboard</a><a class="secondary" href="?p=logout">Logout</a>';}else{echo '<a class="secondary" href="?p=register">Register</a><a class="secondary" href="?p=login">Login</a><a class="secondary" href="?p=admin">Admin</a>';}echo '</div></div>';}
function footer_html(){echo '<div class="footer">Dark-Money is managed through a manual-review safety model. This site does not store Bitcoin private keys.</div></div></body></html>';}
function flash($msg,$err){if($msg)echo '<div class="notice good">'.e($msg).'</div>';if($err)echo '<div class="notice bad">'.e($err).'</div>';}
function form_csrf(){echo '<input type="hidden" name="csrf" value="'.e(csrf()).'">';}

header_html(ucfirst(str_replace('_',' ',$p)));flash($msg,$err);

if($p==='home'){
    echo '<div class="card hero"><h1>Dark-Money value management with clear ownership shares.</h1><p class="muted">'.e(file_get_contents(__DIR__.'/free.txt')).'</p><p><a class="btn" href="?p=register">Create account</a></p></div><div class="grid"><div class="card"><h3>One unit</h3><p class="muted">Dark-Money is one shared value unit divided by percentage ownership.</p></div><div class="card"><h3>MFA required</h3><p class="muted">Each user uses password login plus TOTP-based MFA.</p></div><div class="card"><h3>Manual review</h3><p class="muted">BTC-facing actions are reviewed before ledger changes are finalized.</p></div></div>';
}
elseif($p==='register'){
    echo '<div class="card"><h2>Create account</h2><form method="post">';form_csrf();echo '<div class="field"><label>Password</label><input type="password" name="password" required minlength="10"></div><div class="field"><label>Your Bitcoin wallet address</label><input name="wallet" required></div><button class="btn">Create and configure MFA</button></form></div>';
}
elseif($p==='mfa'){
    $uid=$_GET['uid'] ?? ($_SESSION['pending_uid'] ?? '');$st=db()->prepare('SELECT * FROM users WHERE user_id=?');$st->execute([$uid]);$u=$st->fetch(PDO::FETCH_ASSOC);if(!$u){echo '<div class="card bad">Account not found.</div>';}else{$issuer=rawurlencode($config['app_name']);$label=rawurlencode($config['app_name'].':'.$u['user_id']);$uri="otpauth://totp/$label?secret={$u['totp_secret']}&issuer=$issuer&digits=6&period=30";echo '<div class="card"><h2>Configure MFA</h2><p>Your User ID is <code>'.e($u['user_id']).'</code>. Save it securely.</p><p>Add this TOTP secret to your authenticator app:</p><p><code>'.e($u['totp_secret']).'</code></p><p class="muted">Manual URI for compatible apps:</p><p><code>'.e($uri).'</code></p><p><a class="btn" href="?p=login">Continue to login</a></p></div>';}
}
elseif($p==='login'){
    echo '<div class="card"><h2>User login</h2><form method="post">';form_csrf();echo '<div class="field"><label>User ID</label><input name="user_id" required></div><div class="field"><label>Password</label><input type="password" name="password" required></div><div class="field"><label>TOTP code</label><input name="totp" inputmode="numeric" required></div><button class="btn">Login</button></form></div>';
}
elseif($p==='dashboard'){
    require_user();$u=user();echo '<div class="grid"><div class="card"><h3>User ID</h3><p><code>'.e($u['user_id']).'</code></p></div><div class="card"><h3>Status</h3><p>'.($u['active']?'<span class="good">Active</span>':'<span class="bad">Not active</span>').'</p></div><div class="card"><h3>Balance</h3><p>$'.money($u['balance_usd']).'</p></div><div class="card"><h3>Share</h3><p>'.money(share($u['balance_usd'])).'%</p></div></div><div class="tabs"><a class="btn" href="?p=activation_request">Activate</a><a class="btn" href="?p=deposit">Deposit</a><a class="btn" href="?p=withdraw">Withdraw</a><a class="btn" href="?p=transfer">Transfer</a><a class="btn secondary" href="?p=history">History</a><a class="btn secondary" href="?p=settings">Settings</a></div><div class="card"><h2>Payment guide</h2><p class="muted">Activation requires 11 USD equivalent BTC. The reviewed ledger allocation is 10 USD to Dark-Money and 1 USD fee.</p><p>Dark-Money address: <code>'.e($config['dark_money_address']).'</code></p><p>Fee address: <code>'.e($config['fee_address']).'</code></p></div>';
}
elseif($p==='activation_request'){
    require_user();echo '<div class="card"><h2>Activation</h2><p>Send 11 USD equivalent BTC according to the payment guide, then submit this request for review.</p><form method="post">';form_csrf();echo '<button class="btn">Submit activation review request</button></form></div>';
}
elseif($p==='deposit'){
    require_user();echo '<div class="card"><h2>Deposit</h2><p class="muted">Enter the total USD value sent. A 1 USD fee is deducted during approval.</p><form method="post">';form_csrf();echo '<div class="field"><label>Total USD value</label><input type="number" step="0.01" min="1.01" name="amount" required></div><button class="btn">Submit deposit request</button></form></div>';
}
elseif($p==='withdraw'){
    require_user();$u=user();echo '<div class="card"><h2>Withdraw</h2><p class="muted">The entered amount is deducted from your balance. Payout is amount minus 1 USD fee after manual approval.</p><p>Available: $'.money($u['balance_usd']).'</p><form method="post">';form_csrf();echo '<div class="field"><label>USD amount to deduct</label><input type="number" step="0.01" min="1.01" max="'.e($u['balance_usd']).'" name="amount" required></div><button class="btn">Submit withdrawal request</button></form></div>';
}
elseif($p==='transfer'){
    require_user();echo '<div class="card"><h2>Internal transfer</h2><p class="muted">Transfer value to another active User ID. No fee applies.</p><form method="post">';form_csrf();echo '<div class="field"><label>Target User ID</label><input name="to_user" required></div><div class="field"><label>USD amount</label><input type="number" step="0.01" min="0.01" name="amount" required></div><button class="btn">Transfer</button></form></div>';
}
elseif($p==='history'){
    require_user();$u=user();$st=db()->prepare('SELECT * FROM transactions WHERE from_user=? OR to_user=? ORDER BY id DESC LIMIT 80');$st->execute([$u['user_id'],$u['user_id']]);echo '<div class="card"><h2>Transaction history</h2><table><tr><th>ID</th><th>Type</th><th>Status</th><th>Amount</th><th>Fee</th><th>Date</th></tr>';foreach($st as $r){echo '<tr><td>'.e($r['id']).'</td><td>'.e($r['type']).'</td><td>'.e($r['status']).'</td><td>$'.money($r['amount_usd']).'</td><td>$'.money($r['fee_usd']).'</td><td>'.e($r['created_at']).'</td></tr>';}echo '</table></div>';
}
elseif($p==='settings'){
    require_user();$u=user();echo '<div class="card"><h2>Account settings</h2><form method="post">';form_csrf();echo '<div class="field"><label>Bitcoin wallet address</label><input name="wallet" value="'.e($u['wallet_address']).'" required></div><button class="btn">Save wallet</button></form></div>';
}
elseif($p==='admin'){
    echo '<div class="card"><h2>Admin login</h2><form method="post">';form_csrf();echo '<div class="field"><label>Admin user</label><input name="admin_user" value="admin" required></div><div class="field"><label>Password</label><input type="password" name="password" required></div><button class="btn">Login</button></form></div>';
}
elseif($p==='admin_dashboard'){
    require_admin();$users=db()->query('SELECT * FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);$txs=db()->query('SELECT * FROM transactions ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);echo '<div class="tabs"><a class="btn secondary" href="?p=admin_logout">Admin logout</a></div><div class="grid"><div class="card"><h3>Total Dark-Money value</h3><p>$'.money(total_value()).'</p></div><div class="card"><h3>Users</h3><p>'.count($users).'</p></div></div><div class="card"><h2>Users</h2><table><tr><th>User ID</th><th>Active</th><th>Balance</th><th>Share</th><th>Wallet</th></tr>';foreach($users as $u){echo '<tr><td><code>'.e($u['user_id']).'</code></td><td>'.($u['active']?'yes':'no').'</td><td>$'.money($u['balance_usd']).'</td><td>'.money(share($u['balance_usd'])).'%</td><td><code>'.e($u['wallet_address']).'</code></td></tr>';}echo '</table></div><div class="card"><h2>Transactions</h2><table><tr><th>ID</th><th>Type</th><th>Status</th><th>User</th><th>Amount</th><th>Fee</th><th>Action</th></tr>';foreach($txs as $t){echo '<tr><td>'.e($t['id']).'</td><td>'.e($t['type']).'</td><td>'.e($t['status']).'</td><td>'.e($t['from_user']).'</td><td>$'.money($t['amount_usd']).'</td><td>$'.money($t['fee_usd']).'</td><td>';if($t['status']==='pending'){echo '<form method="post" action="?p=admin_action" style="display:inline">';form_csrf();echo '<input type="hidden" name="txid" value="'.e($t['id']).'"><button class="btn" name="action" value="approve">Approve</button> <button class="btn secondary" name="action" value="reject">Reject</button></form>';}echo '</td></tr>';}echo '</table></div>';
}
else{echo '<div class="card"><h2>Page not found</h2></div>';}

footer_html();
PHP;

        $setupLock = 'Installed at ' . $createdAt . "\n";

        smt_write_file(__DIR__ . '/config.php', $config);
        smt_write_file(__DIR__ . '/free.txt', $freeTxt);
        smt_write_file(__DIR__ . '/.htaccess', $htaccess);
        smt_write_file(__DIR__ . '/data/.htaccess', $dataHtaccess);
        smt_write_file(__DIR__ . '/assets/style.css', $style);
        smt_write_file(__DIR__ . '/index.php', $index);
        smt_write_file(__DIR__ . '/data/setup.lock', $setupLock);
        $done = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($installerTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
body{margin:0;background:#070911;color:#f4f7fb;font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif}.wrap{max-width:880px;margin:0 auto;padding:32px}.card{background:#111827;border:1px solid #263247;border-radius:22px;padding:24px;box-shadow:0 18px 60px rgba(0,0,0,.35)}h1{margin-top:0}.field{display:flex;flex-direction:column;gap:7px;margin:14px 0}input{background:#070911;color:#f4f7fb;border:1px solid #263247;border-radius:13px;padding:13px}.btn{background:linear-gradient(135deg,#7c4dff,#4f46e5);border:0;color:white;border-radius:13px;padding:12px 16px;font-weight:800;cursor:pointer}.bad{color:#ff5c7a}.good{color:#34d399}.muted{color:#9ba8bd}code{background:#070911;border:1px solid #263247;padding:3px 6px;border-radius:8px;word-break:break-all}
</style>
</head>
<body><div class="wrap"><div class="card">
<h1>Secure Money Transfer Setup</h1>
<?php if ($done): ?>
<p class="good">Installation completed.</p>
<p>Generated files:</p>
<ul><li><code>index.php</code></li><li><code>config.php</code></li><li><code>.htaccess</code></li><li><code>free.txt</code></li><li><code>assets/style.css</code></li><li><code>data/setup.lock</code></li></ul>
<p class="bad">Important: delete this setup.php file now or keep it blocked by server rules.</p>
<p><a class="btn" href="index.php">Open website</a></p>
<?php else: ?>
<p class="muted">This installer creates a dark-themed English website for Dark-Money with SQLite storage, password hashing, TOTP MFA, CSRF protection, manual-review BTC-facing actions and no private-key storage.</p>
<?php if ($errors): ?><ul class="bad"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post">
<div class="field"><label>Base URL</label><input name="base_url" value="<?php echo htmlspecialchars((!empty($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost').rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'), ENT_QUOTES, 'UTF-8'); ?>" required></div>
<div class="field"><label>Fee Bitcoin address</label><input name="fee_address" required></div>
<div class="field"><label>Dark-Money Bitcoin address</label><input name="dark_address" required></div>
<div class="field"><label>Admin password</label><input type="password" name="admin_password" minlength="12" required></div>
<div class="field"><label>Repeat admin password</label><input type="password" name="admin_password2" minlength="12" required></div>
<button class="btn">Generate website files</button>
</form>
<?php endif; ?>
</div></div></body></html>
