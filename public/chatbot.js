document.addEventListener("DOMContentLoaded", function () {
  const chatButton = document.createElement("div");
  chatButton.id = "mojchat-bubble";
  chatButton.innerHTML = "💬";

  const chatBox = document.createElement("div");
  chatBox.id = "mojchat-box";
  chatBox.innerHTML = `
        <div id="mojchat-header">AI Chatbot</div>
        <div id="mojchat-messages"></div>
        <input type="text" id="mojchat-input" placeholder="Upiši pitanje..." />
    `;

  document.body.appendChild(chatButton);
  document.body.appendChild(chatBox);

  chatButton.onclick = () => {
    chatBox.classList.toggle("open");
  };

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
    div.innerHTML = `<strong>${tko}:</strong> ${poruka}`;
    document.getElementById("mojchat-messages").appendChild(div);
  }
});
