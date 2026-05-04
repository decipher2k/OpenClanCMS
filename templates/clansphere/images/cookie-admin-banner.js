/* ClanSphere Admin Cookie Banner */
(function(){
  if (document.cookie.split('; ').find(function(r) { return r.startsWith('cookie_ok=') })) return;
  var b = document.createElement('div');
  b.innerHTML = '<div style="background:#1a1a1a;color:#ccc;border-bottom:2px solid #494;padding:8px 20px;text-align:center;font-size:13px;font-family:sans-serif;">Diese Website verwendet <strong>ausschliesslich technisch notwendige Cookies</strong>. <button onclick="document.cookie=\'cookie_ok=1;path=/;max-age=31536000;SameSite=Lax\';this.parentNode.parentNode.remove()" style="background:#363;color:#cfc;border:1px solid #494;padding:3px 10px;margin-left:10px;cursor:pointer;font-size:12px;">OK</button></div>';
  document.body.insertBefore(b, document.body.firstChild);
})();
