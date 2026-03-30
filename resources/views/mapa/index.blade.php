<!DOCTYPE html>
<html>
<head>
    <title>Mapa de Lugares</title>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC4hXrlfWN92ar0nfxK64vDaN0UOtsSg-k"></script>

    <!-- Cluster -->
    <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>

    <style>
        body { margin: 0; }
        #map { height: 100vh; width: 100%; }
    </style>
</head>
<body>

<div id="map"></div>

<script>
async function initMap() {

    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: { lat: 20.97, lng: -89.62 } // Mérida
    });

    const response = await fetch('/api/points');
    const data = await response.json();

    const markers = data.map(item => {

        // Iconos por categoría
        let icon = null;

        if (item.category === "cafeteria") {
            icon = "http://maps.google.com/mapfiles/ms/icons/blue-dot.png";
        } else if (item.category === "restaurante") {
            icon = "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
        } else {
            icon = "http://maps.google.com/mapfiles/ms/icons/green-dot.png";
        }

        const marker = new google.maps.Marker({
            position: { lat: item.lat, lng: item.lng },
            icon: icon
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="min-width:200px">
                    <strong>${item.name}</strong><br>
                    Categoría: ${item.category}<br>
                    Precio: ${item.price}
                </div>
            `
        });

        marker.addListener("click", () => {
            infoWindow.open(map, marker);
        });

        return marker;
    });

    new markerClusterer.MarkerClusterer({
        map,
        markers
    });
}

initMap();
</script>

</body>
</html>