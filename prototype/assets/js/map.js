document.addEventListener("DOMContentLoaded", () => {
  const mapEl = document.getElementById("map");
  if (!mapEl) return;

  // Μπορείς να αλλάξεις τις συντεταγμένες σε ό,τι θες
  const campusLatLng = [40.6401, 22.9444];

  const map = L.map("map").setView(campusLatLng, 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  L.marker(campusLatLng).addTo(map).bindPopup("University Campus").openPopup();
});
