(function () {
    "use strict";

    var previousUnreadCount = null;
    var toastCounter = 0;

    function ensureToastStyles() {
        if (document.getElementById("notification-toast-styles")) {
            return;
        }

        var style = document.createElement("style");
        style.id = "notification-toast-styles";
        style.textContent = ""
            + ".notification-toast-wrap{position:fixed;top:88px;right:16px;z-index:2000;display:flex;flex-direction:column;gap:10px;}"
            + ".notification-toast{min-width:280px;max-width:360px;background:#1f2937;color:#fff;border-radius:10px;padding:12px 14px;box-shadow:0 8px 20px rgba(0,0,0,.2);opacity:0;transform:translateY(-10px);transition:all .25s ease;}"
            + ".notification-toast.show{opacity:1;transform:translateY(0);}"
            + ".notification-toast-title{font-weight:700;font-size:.95rem;margin:0 0 4px 0;}"
            + ".notification-toast-text{font-size:.85rem;opacity:.92;margin:0 0 8px 0;}"
            + ".notification-toast a{display:inline-block;color:#fff;text-decoration:none;font-size:.8rem;font-weight:600;background:rgba(255,255,255,.16);padding:6px 10px;border-radius:6px;}"
            + "@media (max-width: 576px){.notification-toast-wrap{left:12px;right:12px;}.notification-toast{min-width:0;max-width:none;}}";
        document.head.appendChild(style);
    }

    function getToastContainer() {
        var existing = document.getElementById("notification-toast-wrap");
        if (existing) {
            return existing;
        }

        var wrap = document.createElement("div");
        wrap.id = "notification-toast-wrap";
        wrap.className = "notification-toast-wrap";
        document.body.appendChild(wrap);
        return wrap;
    }

    function showToast(unreadCount, newItems) {
        ensureToastStyles();

        var container = getToastContainer();
        var toast = document.createElement("div");
        var toastId = "notification-toast-" + String(++toastCounter);

        toast.className = "notification-toast";
        toast.id = toastId;

        var itemText = newItems === 1 ? "new notification" : (String(newItems) + " new notifications");
        toast.innerHTML = ""
            + "<p class='notification-toast-title'>New Notification</p>"
            + "<p class='notification-toast-text'>You have " + itemText + ". Total unread: " + String(unreadCount) + "</p>"
            + "<a href='notifications.php'>Open Notifications</a>";

        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add("show");
        });

        setTimeout(function () {
            toast.classList.remove("show");
            setTimeout(function () {
                if (toast && toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 260);
        }, 4500);
    }

    function updateBadge(el, count) {
        if (!el) {
            return;
        }

        if (count > 0) {
            el.textContent = String(count);
            el.style.display = "inline-block";
        } else {
            el.textContent = "";
            el.style.display = "none";
        }
    }

    async function pollCount() {
        var badges = document.querySelectorAll(".js-notification-badge[data-endpoint]");
        if (!badges.length) {
            return;
        }

        var endpoint = badges[0].getAttribute("data-endpoint");

        try {
            var response = await fetch(endpoint, {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            });

            if (!response.ok) {
                return;
            }

            var data = await response.json();
            if (!data || data.success !== true) {
                return;
            }

            var unreadCount = Number(data.count || 0);

            badges.forEach(function (badge) {
                updateBadge(badge, unreadCount);
            });

            if (previousUnreadCount !== null && unreadCount > previousUnreadCount) {
                showToast(unreadCount, unreadCount - previousUnreadCount);
            }

            previousUnreadCount = unreadCount;
        } catch (error) {
            // Fail silently to avoid interrupting page behavior.
        }
    }

    pollCount();
    setInterval(pollCount, 30000);
})();
