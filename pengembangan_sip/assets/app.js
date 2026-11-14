(function () {
  var body = document.body;

  // Menu samping di layar kecil
  var btn = document.getElementById("menuBtn");
  var overlay = document.getElementById("overlay");
  if (btn) btn.addEventListener("click", function () { body.classList.toggle("nav-open"); });
  if (overlay) overlay.addEventListener("click", function () { body.classList.remove("nav-open"); });

  // Konfirmasi dan isian alasan sebelum kirim formulir
  document.addEventListener("submit", function (ev) {
    var f = ev.target;
    if (f.dataset.confirm && !window.confirm(f.dataset.confirm)) { ev.preventDefault(); return; }
    if (f.dataset.prompt) {
      var jawab = window.prompt(f.dataset.prompt, "");
      if (jawab === null) { ev.preventDefault(); return; }
      var h = f.querySelector('input[name="' + f.dataset.promptField + '"]');
      if (h) h.value = jawab;
    }
  });

  // Lihat atau sembunyikan password
  document.querySelectorAll("[data-toggle-pw]").forEach(function (b) {
    b.addEventListener("click", function () {
      var i = document.getElementById(b.dataset.togglePw);
      if (!i) return;
      i.type = i.type === "password" ? "text" : "password";
      b.textContent = i.type === "password" ? "Lihat" : "Sembunyikan";
    });
  });

  // Pesan hilang otomatis
  document.querySelectorAll("[data-autohide]").forEach(function (a) {
    setTimeout(function () { a.style.display = "none"; }, 7000);
  });

  // Cetak
  document.querySelectorAll(".js-print").forEach(function (b) {
    b.addEventListener("click", function () { window.print(); });
  });

  // Baris alat dinamis pada formulir peminjaman
  var wadah = document.getElementById("item-wadah");
  var tambah = document.getElementById("tambah-item");
  function pasangBaris(row) {
    var sel = row.querySelector("select");
    var qty = row.querySelector('input[type="number"]');
    var hapus = row.querySelector(".hapus-item");
    function batas() {
      var o = sel.options[sel.selectedIndex];
      var stok = o && o.dataset.stok ? parseInt(o.dataset.stok, 10) : 1;
      qty.max = stok;
      if (parseInt(qty.value, 10) > stok) qty.value = stok;
    }
    sel.addEventListener("change", batas);
    if (hapus) hapus.addEventListener("click", function () {
      if (wadah.querySelectorAll(".item-row").length > 1) row.remove();
    });
    batas();
  }
  if (wadah && tambah) {
    wadah.querySelectorAll(".item-row").forEach(pasangBaris);
    tambah.addEventListener("click", function () {
      var baris = wadah.querySelector(".item-row");
      var baru = baris.cloneNode(true);
      baru.querySelector("select").value = "";
      baru.querySelector('input[type="number"]').value = 1;
      wadah.appendChild(baru);
      pasangBaris(baru);
    });
  }
})();
