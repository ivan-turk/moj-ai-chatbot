jQuery(document).ready(function ($) {
  console.log("admin.js učitan ✅"); // ➔ provjera u konzoli

  $("#mojchat_upload_button").on("click", function (e) {
    e.preventDefault();

    // Otvori Media uploader
    var custom_uploader = wp.media({
      title: "Odaberi sliku za avatara",
      button: {
        text: "Postavi ovu sliku",
      },
      multiple: false,
    });

    custom_uploader.on("select", function () {
      var attachment = custom_uploader
        .state()
        .get("selection")
        .first()
        .toJSON();
      $("#mojchat_avatar_url").val(attachment.url);
    });

    custom_uploader.open();
  });
});
