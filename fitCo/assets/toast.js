/*
 * FITCO toast notifications.
 *
 * Drop-in replacement for alert() — include this once on a page, then call
 * Toast.show("message", "success" | "error" | "info").
 *
 * Usage:
 *   <script src="/mini-projectTEMP/fitCo/assets/toast.js"></script>
 *   <script>
 *     Toast.show("Password reset successful.", "success");
 *     // if you need to redirect after showing it, wait for the toast:
 *     setTimeout(function(){ window.location.href = "../login/login.php"; }, 1400);
 *   </script>
 */
(function () {
  "use strict";
  if (window.Toast) return;

  var CSS =
    "#fitco-toast-region{position:fixed;top:20px;right:20px;z-index:99999;" +
    "display:flex;flex-direction:column;gap:10px;pointer-events:none;" +
    "font-family:'Inter',sans-serif;}" +
    ".fitco-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:10px;" +
    "width:290px;max-width:calc(100vw - 40px);background:#fdfdfc;" +
    "border:1px solid rgba(23,20,15,0.12);border-radius:12px;padding:12px 14px;" +
    "box-shadow:0 1px 2px rgba(23,20,15,0.04),0 12px 28px -14px rgba(23,20,15,0.28);" +
    "opacity:0;transform:translateY(-10px) scale(0.98);" +
    "transition:opacity 220ms cubic-bezier(0.23,1,0.32,1),transform 220ms cubic-bezier(0.23,1,0.32,1);}" +
    ".fitco-toast.is-visible{opacity:1;transform:translateY(0) scale(1);}" +
    ".fitco-toast.is-leaving{opacity:0;transform:translateY(-10px) scale(0.98);}" +
    ".fitco-toast-icon{flex:none;width:20px;height:20px;border-radius:50%;" +
    "display:flex;align-items:center;justify-content:center;margin-top:1px;}" +
    ".fitco-toast--success .fitco-toast-icon{background:rgba(61,122,86,0.12);color:#3d7a56;}" +
    ".fitco-toast--error .fitco-toast-icon{background:rgba(179,65,58,0.12);color:#b3413a;}" +
    ".fitco-toast--info .fitco-toast-icon{background:rgba(23,20,15,0.08);color:#17140f;}" +
    ".fitco-toast-icon svg{width:11px;height:11px;}" +
    ".fitco-toast-body{flex:1;font-size:13px;line-height:1.45;color:#17140f;padding-top:1px;}" +
    ".fitco-toast-close{flex:none;background:none;border:none;color:#726d5e;cursor:pointer;" +
    "font-size:14px;line-height:1;padding:2px;margin:-2px -2px 0 0;border-radius:5px;}" +
    ".fitco-toast-close:hover{background:rgba(23,20,15,0.08);color:#17140f;}" +
    "@media (max-width:480px){#fitco-toast-region{left:20px;right:20px;top:14px;}" +
    ".fitco-toast{width:auto;}}" +
    "@media (prefers-reduced-motion:reduce){.fitco-toast{transition:none;}}";

  var styleEl = document.createElement("style");
  styleEl.textContent = CSS;
  document.head.appendChild(styleEl);

  var region = document.createElement("div");
  region.id = "fitco-toast-region";
  region.setAttribute("aria-live", "polite");
  region.setAttribute("aria-atomic", "true");

  function mountRegion() {
    if (document.body && !document.body.contains(region)) {
      document.body.appendChild(region);
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountRegion);
  } else {
    mountRegion();
  }

  var ICONS = {
    success:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>',
    error:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    info:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>'
  };

  function show(message, type, duration) {
    mountRegion();
    type = ICONS[type] ? type : "info";
    duration = duration || 3200;

    var el = document.createElement("div");
    el.className = "fitco-toast fitco-toast--" + type;
    el.innerHTML =
      '<span class="fitco-toast-icon">' + ICONS[type] + "</span>" +
      '<span class="fitco-toast-body"></span>' +
      '<button type="button" class="fitco-toast-close" aria-label="Dismiss">&times;</button>';
    el.querySelector(".fitco-toast-body").textContent = message;
    region.appendChild(el);

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.classList.add("is-visible");
      });
    });

    var timer = setTimeout(dismiss, duration);
    el.querySelector(".fitco-toast-close").addEventListener("click", function () {
      clearTimeout(timer);
      dismiss();
    });

    function dismiss() {
      el.classList.remove("is-visible");
      el.classList.add("is-leaving");
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 220);
    }

    return { dismiss: dismiss };
  }

  window.Toast = { show: show };
})();
