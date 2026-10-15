(function () {
  "use strict";

  var csrfToken = document.querySelector('meta[name="csrf-token"]');
  csrfToken = csrfToken ? csrfToken.content : "";

  function iniciais(nome) {
    var partes = nome.trim().split(/\s+/).filter(Boolean);
    if (!partes.length) return "?";
    var primeira = partes[0].charAt(0);
    var ultima = partes.length > 1 ? partes[partes.length - 1].charAt(0) : "";
    return (primeira + ultima).toUpperCase();
  }

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
          var article = form.closest("article[data-photo-id]");
          var list = article ? article.querySelector("[data-comment-list]") : null;
          if (list) {
            var row = document.createElement("div");
            row.className = "comment-row";
            row.setAttribute("data-comment-row", res.id);

            var avatar = document.createElement("div");
            avatar.className = "avatar avatar-xs";
            avatar.textContent = iniciais(res.autor);
            row.appendChild(avatar);

            var body = document.createElement("div");
            body.className = "comment-row-body";
            var linha = document.createElement("span");
            var strong = document.createElement("strong");
            strong.textContent = res.autor;
            linha.appendChild(strong);
            linha.appendChild(document.createTextNode(" " + res.texto));
            var quando = document.createElement("span");
            quando.className = "text-xs text-muted";
            quando.textContent = "agora";
            body.appendChild(linha);
            body.appendChild(quando);
            row.appendChild(body);

            if (document.body.getAttribute("data-is-admin") === "1") {
              var delBtn = document.createElement("button");
              delBtn.type = "button";
              delBtn.className = "comment-delete-btn";
              delBtn.setAttribute("data-comment-delete-btn", "");
              delBtn.setAttribute("data-comment-id", res.id);
              delBtn.title = "Excluir comentário (admin)";
              delBtn.textContent = "✕";
              row.appendChild(delBtn);
            }
            list.appendChild(row);
          }
          var countEl = article ? article.querySelector("[data-comment-count]") : null;
          if (countEl) {
            countEl.textContent = String(parseInt(countEl.textContent, 10) + 1);
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

  // --- Seguir / deixar de seguir ---
  document.querySelectorAll("[data-follow-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var userId = btn.getAttribute("data-user-id");
      postJSON("/api/follow.php", { user_id: userId })
        .then(function (res) {
          if (res.seguindo) {
            btn.textContent = "Deixar de seguir";
            btn.classList.add("btn-ghost");
          } else {
            btn.textContent = "Seguir";
            btn.classList.remove("btn-ghost");
          }
          btn.setAttribute("data-following", res.seguindo ? "1" : "0");
        })
        .catch(handleError);
    });
  });

  // --- Excluir foto (Meu perfil) ---
  document.querySelectorAll("[data-delete-photo-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (!confirm("Excluir essa foto? Não pode ser desfeito.")) return;
      var photoId = btn.getAttribute("data-photo-id");
      postJSON("/api/photo_delete.php", { photo_id: photoId })
        .then(function () {
          var card = document.querySelector('[data-my-photo="' + photoId + '"]');
          if (card) card.remove();
        })
        .catch(handleError);
    });
  });

  // --- Excluir comentário (admin) — delegado, funciona também em comentários
  // adicionados na hora (sem reload), que não existiam quando a página carregou.
  document.addEventListener("click", function (ev) {
    var btn = ev.target.closest("[data-comment-delete-btn]");
    if (!btn) return;
    if (!confirm("Excluir este comentário? Não pode ser desfeito.")) return;
    var commentId = btn.getAttribute("data-comment-id");
    postJSON("/api/comment_delete.php", { comment_id: commentId })
      .then(function () {
        var row = document.querySelector('[data-comment-row="' + commentId + '"]');
        var article = row ? row.closest("article[data-photo-id]") : null;
        if (row) row.remove();
        var countEl = article ? article.querySelector("[data-comment-count]") : null;
        if (countEl) {
          countEl.textContent = String(Math.max(0, parseInt(countEl.textContent, 10) - 1));
        }
      })
      .catch(handleError);
  });

  // --- Denunciar foto ---
  document.querySelectorAll("[data-report-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var reason = prompt("Por que você está denunciando essa foto?");
      if (!reason || !reason.trim()) return;
      var photoId = btn.getAttribute("data-photo-id");
      postJSON("/api/report.php", { photo_id: photoId, reason: reason.trim() })
        .then(function () {
          btn.disabled = true;
          btn.textContent = "Denunciada";
        })
        .catch(handleError);
    });
  });

  // --- Excluir conta ---
  var deleteForm = document.querySelector("[data-delete-account-form]");
  if (deleteForm) {
    deleteForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      if (!confirm("Tem certeza? Isso apaga sua conta, fotos e conversas permanentemente.")) {
        return;
      }
      var password = deleteForm.querySelector('input[name="password"]').value;
      postJSON("/api/account_delete.php", { password: password })
        .then(function () {
          window.location.href = "/index.php";
        })
        .catch(handleError);
    });
  }

  // --- Suspender/reativar usuário (admin) ---
  document.querySelectorAll("[data-user-status-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var userId = btn.getAttribute("data-user-id");
      var status = btn.getAttribute("data-status");
      if (status === "suspended" && !confirm("Suspender esta conta? A pessoa não vai mais conseguir entrar.")) {
        return;
      }
      postJSON("/api/admin_user_status.php", { user_id: userId, status: status })
        .then(function () {
          var row = document.querySelector('[data-user-row="' + userId + '"]');
          if (row) {
            var label = row.querySelector("[data-user-status-label]");
            if (label) label.textContent = status === "active" ? "Ativa" : "Suspensa";
            var actionBtn = row.querySelector("[data-user-status-btn]");
            if (actionBtn) {
              if (status === "active") {
                actionBtn.textContent = "Suspender";
                actionBtn.setAttribute("data-status", "suspended");
                actionBtn.className = "btn btn-ghost btn-sm";
              } else {
                actionBtn.textContent = "Reativar";
                actionBtn.setAttribute("data-status", "active");
                actionBtn.className = "btn btn-sm";
              }
            }
          } else {
            btn.disabled = true;
            btn.textContent = "Conta suspensa";
          }
        })
        .catch(handleError);
    });
  });

  // --- Resolver denúncia (admin) ---
  document.querySelectorAll("[data-report-resolve-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var reportId = btn.getAttribute("data-report-id");
      var action = btn.getAttribute("data-action");
      postJSON("/api/admin_report_resolve.php", { report_id: reportId, action: action })
        .then(function () {
          var row = document.querySelector('[data-report-row="' + reportId + '"]');
          if (row) row.remove();
        })
        .catch(handleError);
    });
  });

  // --- Moderação de fotos (admin) ---
  document.querySelectorAll("[data-mod-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var photoId = btn.getAttribute("data-photo-id");
      var action = btn.getAttribute("data-action");
      postJSON("/api/admin_moderate.php", { photo_id: photoId, action: action })
        .then(function () {
          var card = document.querySelector('[data-mod-photo="' + photoId + '"]');
          if (card) card.remove();
          var countEl = document.querySelector("[data-pending-count]");
          if (countEl) {
            countEl.textContent = String(Math.max(0, parseInt(countEl.textContent, 10) - 1));
          }
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

  // --- UF -> cidade em cascata (cadastro e busca) ---
  function carregarCidades(citySelect, uf, placeholder) {
    citySelect.innerHTML = "";
    if (!uf) {
      citySelect.appendChild(new Option("Escolha a UF primeiro", ""));
      return Promise.resolve([]);
    }
    citySelect.appendChild(new Option("Carregando...", ""));
    return fetch("/api/cities.php?uf=" + encodeURIComponent(uf))
      .then(function (res) { return res.json(); })
      .then(function (data) {
        citySelect.innerHTML = "";
        citySelect.appendChild(new Option(placeholder, ""));
        var cidades = data.cidades || [];
        cidades.forEach(function (cidade) {
          citySelect.appendChild(new Option(cidade, cidade));
        });
        return cidades;
      })
      .catch(function () {
        citySelect.innerHTML = "";
        citySelect.appendChild(new Option("Erro ao carregar cidades", ""));
        return [];
      });
  }

  document.querySelectorAll("[data-state-select]").forEach(function (select) {
    var citySelect = document.getElementById(select.getAttribute("data-city-target"));
    if (!citySelect) return;
    var placeholder = citySelect.getAttribute("data-empty-label") || "Selecione a cidade";

    select.addEventListener("change", function () {
      carregarCidades(citySelect, select.value, placeholder);
    });

    // --- Autodetectar cidade/UF pela geolocalização do navegador (cadastro) ---
    if (select.hasAttribute("data-autodetect-location") && navigator.geolocation) {
      var status = document.querySelector("[data-location-status]");
      var mostrarStatus = function (msg) {
        if (!status) return;
        status.textContent = msg;
        status.style.display = msg ? "" : "none";
      };

      mostrarStatus("Detectando sua localização...");
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          var url = "https://api.bigdatacloud.net/data/reverse-geocode-client?latitude="
            + pos.coords.latitude + "&longitude=" + pos.coords.longitude + "&localityLanguage=pt";
          fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
              var uf = (data.principalSubdivisionCode || "").replace("BR-", "");
              var cidade = data.city || data.locality || "";
              if (!uf) {
                mostrarStatus("");
                return;
              }
              select.value = uf;
              carregarCidades(citySelect, uf, placeholder).then(function (cidades) {
                if (cidade && cidades.indexOf(cidade) !== -1) {
                  citySelect.value = cidade;
                  mostrarStatus("Localização detectada: " + cidade + "/" + uf + ". Confira e corrija se precisar.");
                } else {
                  mostrarStatus("UF detectada: " + uf + ". Escolha sua cidade na lista.");
                }
              });
            })
            .catch(function () { mostrarStatus(""); });
        },
        function () { mostrarStatus(""); },
        { timeout: 8000 }
      );
    }
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

    function ensureConversationInSidebar() {
      var list = document.querySelector("[data-conversation-list]");
      if (!list || list.querySelector('[data-conversation-with="' + withId + '"]')) return;

      var noConv = list.querySelector("[data-no-conversations]");
      if (noConv) noConv.remove();

      list.querySelectorAll(".conversation-item").forEach(function (el) {
        el.classList.remove("active");
      });

      var link = document.createElement("a");
      link.className = "conversation-item active";
      link.href = "/chat.php?with=" + encodeURIComponent(withId);
      link.setAttribute("data-conversation-with", withId);
      link.textContent = chatWindow.getAttribute("data-with-name") || "Conversa";
      list.insertBefore(link, list.firstChild);
    }

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
            ensureConversationInSidebar();
          })
          .catch(handleError);
      });
    }
  }
})();
