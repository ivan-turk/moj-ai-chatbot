document.addEventListener("DOMContentLoaded", function () {
  const chatButton = document.createElement("div");
  chatButton.id = "mojchat-bubble";
  chatButton.innerHTML = "💬";

  const chatBox = document.createElement("div");
  chatBox.id = "mojchat-box";

  // Učitavanje avatara iz WP postavki (ako postoji)
  const avatarUrl = mojchat_ajax.avatar_url || "";

  chatBox.innerHTML = `
    <div id="mojchat-header">
      ${
        avatarUrl
          ? `<img src="${avatarUrl}" id="mojchat-avatar" alt="Maslinko AI">`
          : ""
      }
      <span>Maslinko AI</span>
    </div>
    <div id="mojchat-messages"></div>
    <input type="text" id="mojchat-input" placeholder="Upiši pitanje..." />
  `;

  chatBox.style.display = "none"; // početno skriveno

  document.body.appendChild(chatButton);
  document.body.appendChild(chatBox);

  chatButton.onclick = () => {
    const jeOtvoreno = chatBox.classList.contains("open");

    if (jeOtvoreno) {
      chatBox.classList.remove("open");
      chatBox.style.display = "none";
    } else {
      chatBox.classList.add("open");
      chatBox.style.display = "flex";

      const messages = document.getElementById("mojchat-messages");
      if (messages.childElementCount === 0) {
        dodajPoruku("Maslinko AI", "Pozdrav, kako Vam mogu pomoći?");
      }
    }
  };

  document.addEventListener("click", function (event) {
    const kliknutJeUnutar =
      chatBox.contains(event.target) || chatButton.contains(event.target);
    if (!kliknutJeUnutar) {
      chatBox.classList.remove("open");
      chatBox.style.display = "none";
    }
  });

  const input = document.getElementById("mojchat-input");
  input.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      const msg = input.value.trim();
      if (msg === "") return;
      input.value = "";
      dodajPoruku("Vi", msg);
      fetch(mojchat_ajax.ajax_url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=mojchat_posalji_upit&poruka=${encodeURIComponent(msg)}`,
      })
        .then((res) => res.json())
        .then((data) => dodajPoruku("AI", data.data.odgovor));
    }
  });

  function dodajPoruku(tko, poruka) {
    const div = document.createElement("div");
    div.className =
      "mojchat-msg " + (tko === "Vi" ? "mojchat-user" : "mojchat-ai");
    div.textContent = poruka;
    document.getElementById("mojchat-messages").appendChild(div);

    const messages = document.getElementById("mojchat-messages");
    messages.scrollTop = messages.scrollHeight;
  }
});
