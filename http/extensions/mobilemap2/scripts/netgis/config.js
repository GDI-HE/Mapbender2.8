/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017
 */

/**
 * Configuration namespace.
 * @namespace
 * @name config
 * @memberof netgis
 */
netgis.config = 
{
	MAP_CONTAINER_ID:		"map-container",
	
	/** Initial map center coordinate x in main map projection. */
	INITIAL_CENTER_X:		487000,
	/** Initial map center coordinate y in main map projection. */
	INITIAL_CENTER_Y:		5594000,
	/** Minimum scale to zoom to place search result.  */
	MIN_SEARCH_SCALE:		3000,
	/** Initial map zoom scale (e.g. 10000 = 1:10000). */
	INITIAL_SCALE:			1000000,
	MAP_SCALES:				[ 500, 1000, 3000, 5000, 8000, 10000, 15000, 25000, 50000, 100000, 150000, 250000, 500000, 1000000, 1500000, 2000000 ],
	
	/** List of available map projections (identifier and proj4 definition). */
	MAP_PROJECTIONS:		[
								[ "EPSG:31466", "+proj=tmerc +lat_0=0 +lon_0=6 +k=1 +x_0=2500000 +y_0=0 +ellps=bessel +towgs84=598.1,73.7,418.2,0.202,0.045,-2.455,6.7 +units=m +no_defs" ],

								[ "EPSG:31467", "+proj=tmerc +lat_0=0 +lon_0=9 +k=1 +x_0=3500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs" ],
								[ "EPSG:25832", "+proj=utm +zone=32 +ellps=GRS80 +units=m +no_defs" ],
								[ "EPSG:32632", "+proj=utm +zone=32 +ellps=WGS84 +datum=WGS84 +units=m +no_defs" ]
							],
							
	/** Main projection used by the map view. */
	MAP_PROJECTION:			"EPSG:25832",
	
	/** Map extent (min x, min y, max x, max y). */
	MAP_EXTENT:				[ 403960,5468250,595890,5733150 ],
	
	/** Default map layer opacity (0.0 - 1.0). */
	MAP_DEFAULT_OPACITY:	0.8,
	
	/** Maximum number of map view history entries. */
	MAX_HISTORY:			10,

	/** Default style for GeoRSS points. */
	GEORSS_POINT_RADIUS:		8,
	GEORSS_POINT_FILL_COLOR:	"#861d31",
	GEORSS_POINT_STROKE_COLOR:	"white",
	GEORSS_POINT_STROKE_WIDTH:	2,

	/** Service URLs (avoid proxies by setting to null or empty string). */
	//URL_WMC_PROXY:			"./scripts/proxy.php", //TODO: empty proxy to invoke from js client on same machine!!!
	URL_WMC_PROXY:			"",
	URL_WMC_REQUEST:		"../../php/mod_exportWmc2Json.php",
	//name of serverside conf file for mobilemap - will be used as parameter for mod_exportWmc2Json.php!
	CONF_FILE_NAME:			"mobilemap2",
	
	URL_LAYERS_PROXY:		"",
	URL_LAYERS_REQUEST:		"../../php/mod_callMetadata.php",
	
	URL_SEARCH_PROXY:		"",
	URL_SEARCH_REQUEST:		"../../geoportal/gaz_geom_mobile.php",
	URL_BACKGROUND_HYBRID:		"https://prototyp.geoportal.hessen.de/mapcache/tms/1.0.0/hybrid@UTM32",
	URL_BACKGROUND_AERIAL:	        "https://www.gds-srv.hessen.de/cgi-bin/lika-services/ogc-free-images.ows?language=ger",

	URL_FEATURE_INFO_PROXY:		"",
	
	URL_HEIGHT_PROXY:		"",
	URL_HEIGHT_REQUEST:		"../../extensions/mobilemap/query/rasterqueryWms.php?&lang=de",
	URL_USAGE_TERMS:		"../../php/mod_getWmcDisclaimer.php?id=current"

};
