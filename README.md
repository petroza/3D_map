# PZ Map3D

PZ Map3D is a browser-based 3D map tool for creating simple 3D city and terrain previews from a selected map area.

The current build combines:

- Leaflet map selection
- browser-based 3D preview
- OSM building data
- ČÚZK DMR 5G terrain proxy
- light and dark UI theme
- simple PHP proxy for CORS-safe terrain requests

## Files

```text
3dmap/index.html   Main browser application
3dmap/proxy.php    PHP proxy for ČÚZK DMR 5G ImageServer
```

## Deployment

Upload the `3dmap` folder to a PHP-enabled web hosting. Open `index.html` in the browser. The proxy requires PHP with cURL enabled.

## Data sources

- OpenStreetMap / Overpass API for map and building data
- ČÚZK DMR 5G terrain data through ArcGIS ImageServer
- Leaflet for the map UI

## Legal notes

This repository contains application code only. It does not bundle third-party map data. Respect OpenStreetMap, ČÚZK and third-party API terms when deploying or using the tool.

## License

Application code in this repository is released under the MIT License unless a file states otherwise. Third-party libraries, APIs and data remain under their own licenses and terms.
