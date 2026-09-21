(function () {
  "use strict";

  var csrfToken = document.querySelector('meta[name="csrf-token"]');
  csrfToken = csrfToken ? csrfToken.content : "";

  function postJSON(url, data) {
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken,
      },
      body: JSON.stringify(data || {}),
    }).then(function (res) {
      return res.json().then(function (body) {
        if (!res.ok) {
          var err = new Error(body.erro || "Erro inesperado.");
          err.body = body;
          throw err;
        }
        return body;
      });
    });
  }

  function handleError(err) {
    var msg = err.message || "Erro inesperado.";
    if (err.body && err.body.upgrade) {
      if (confirm(msg + "\n\nQuer conhecer o plano Exclusivo?")) {
        window.location.href = "/planos.php";
      }
      return;
    }
    alert(msg);
  }

  // --- Curtidas ---
  document.querySelectorAll("[data-like-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var photoId = btn.getAttribute("data-photo-id");
      postJSON("/api/like.php", { photo_id: photoId })
        .then(function () {
          btn.disabled = true;
          btn.classList.add("liked");
          var countEl = btn.querySelector("[data-like-count]");
          if (countEl) {
            countEl.textContent = String(parseInt(countEl.textContent, 10) + 1);
          }
        })
        .catch(handleError);
    });
  });

  // --- Comentários ---
  document.querySelectorAll("[data-comment-form]").forEach(function (form) {
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var photoId = form.getAttribute("data-photo-id");
      var input = form.querySelector('input[name="text"]');
      var text = input.value.trim();
      if (!text) return;

      postJSON("/api/comment.php", { photo_id: photoId, text: text })
        .then(function (res) {
          var article = form.closest("[data-photo-id]");
          var list = article ? article.querySelector("[data-comment-list]") : null;
          if (list) {
            var div = document.createElement("div");
            var strong = document.createElement("strong");
            strong.textContent = res.autor + ": ";
            div.appendChild(strong);
            div.appendChild(document.createTextNode(res.texto));
            list.appendChild(div);
          }
          input.value = "";
        })
        .catch(handleError);
    });
  });

  // --- Pedido de amizade ---
  document.querySelectorAll("[data-friend-request-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var toId = btn.getAttribute("data-to-id");
      postJSON("/api/friend_request.php", { to_id: toId })
        .then(function () {
          btn.disabled = true;
          btn.textContent = "Pedido enviado";
        })
        .catch(handleError);
    });
  });

  // --- Responder pedido de amizade ---
  document.querySelectorAll("[data-friend-respond-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var requestId = btn.getAttribute("data-request-id");
      var aceitar = btn.getAttribute("data-aceitar") === "1";
      postJSON("/api/friend_respond.php", { request_id: requestId, aceitar: aceitar })
        .then(function () {
          var row = document.querySelector('[data-request-row="' + requestId + '"]');
          if (row) row.remove();
        })
        .catch(handleError);
    });
  });

  // --- Abas do perfil (Públicas / Reservadas) ---
  document.querySelectorAll("[data-tab-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var tab = btn.getAttribute("data-tab");
      document.querySelectorAll("[data-tab-btn]").forEach(function (b) {
        b.classList.toggle("active", b === btn);
      });
      document.querySelectorAll("[data-tab-panel]").forEach(function (panel) {
        panel.style.display = panel.getAttribute("data-tab-panel") === tab ? "" : "none";
      });
    });
  });

  // --- Chat interno (polling) ---
  var chatWindow = document.querySelector("[data-chat-window]");
  if (chatWindow) {
    var withId = chatWindow.getAttribute("data-with-id");
    var messagesEl = chatWindow.querySelector("[data-chat-messages]");
    var form = chatWindow.querySelector("[data-chat-form]");
    var meId = document.body.getAttribute("data-user-id");

    function renderMessages(mensagens) {
      messagesEl.innerHTML = "";
      mensagens.forEach(function (m) {
        var bubble = document.createElement("div");
        bubble.className = "msg-bubble" + (m.sender_id === meId ? " mine" : "");
        bubble.textContent = m.body;
        messagesEl.appendChild(bubble);
      });
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function loadMessages() {
      fetch("/api/messages.php?with=" + encodeURIComponent(withId))
        .then(function (res) {
          return res.json();
        })
        .then(function (body) {
          if (body.mensagens) renderMessages(body.mensagens);
        })
        .catch(function () {});
    }

    loadMessages();
    var pollInterval = setInterval(loadMessages, 4000);
    window.addEventListener("beforeunload", function () {
      clearInterval(pollInterval);
    });

    if (form) {
      form.addEventListener("submit", function (ev) {
        ev.preventDefault();
        var input = form.querySelector('input[name="text"]');
        var text = input.value.trim();
        if (!text) return;

        postJSON("/api/messages.php", { to_id: withId, text: text })
          .then(function () {
            input.value = "";
            loadMessages();
          })
          .catch(handleError);
      });
    }
  }
})();
