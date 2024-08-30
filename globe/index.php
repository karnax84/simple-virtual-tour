<?php
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once("../db/connection.php");
require_once("../backend/functions.php");
if(check_maintenance_mode('viewer')) {
    if(file_exists("../error_pages/custom/maintenance_viewer.html")) {
        include("../error_pages/custom/maintenance_viewer.html");
    } else {
        include("../error_pages/default/maintenance_viewer.html");
    }
    exit;
}
$v = time();
$array_vt = array();
$s3Client = null;
$s3_url = '';
$globe_type = 'default';
$open_target = 'self';
$cookie_consent = false;
$ga_tracking_id = "";
$id_tour = 0;
if(isset($_GET['set_initial_pos'])) {
    $set_initial_pos = 1;
    if(isset($_GET['id_tour'])) {
        $id_tour = $_GET['id_tour'];
    }
} else {
    $set_initial_pos = 0;
}
$initial_pos = "";
if((isset($_GET['furl'])) || (isset($_GET['code']))) {
    if (isset($_GET['furl'])) {
        $furl = str_replace("'","\'",$_GET['furl']);
        $query = "SELECT id,type,code,name,logo,pointer_size,pointer_color,pointer_border,min_altitude,zoom_duration,default_view,meta_title,meta_description,meta_image,open_target,cookie_consent,ga_tracking_id,initial_pos FROM svt_globes WHERE (friendly_url='$furl' OR code='$furl');";
    }
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $query = "SELECT id,type,code,name,logo,pointer_size,pointer_color,pointer_border,min_altitude,zoom_duration,default_view,meta_title,meta_description,meta_image,open_target,cookie_consent,ga_tracking_id,initial_pos FROM svt_globes WHERE code='$code';";
    }
    $result = $mysqli->query($query);
    if ($result) {
        if ($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $id_s = $row['id'];
            $globe_type = $row['type'];
            $code = $row['code'];
            $name_s = $row['name'];
            $logo_s = $row['logo'];
            $pointer_size = $row['pointer_size'];
            $pointer_color = $row['pointer_color'];
            $pointer_border = $row['pointer_border'];
            $min_altitude =  $row['min_altitude'];
            if(empty($min_altitude)) $min_altitude=0;
            $initial_pos = $row['initial_pos'];
            $zoom_duration = $row['zoom_duration'];
            if(empty($zoom_duration)) $zoom_duration=1;
            if($zoom_duration < 1) $zoom_duration=1;
            $zoom_duration = $zoom_duration*1000;
            $default_view = $row['default_view'];
            $open_target = $row['open_target'];
            $cookie_consent = $row['cookie_consent'];
            $ga_tracking_id = $row['ga_tracking_id'];
            if($set_initial_pos==1) {
                $cookie_consent = false;
                $ga_tracking_id = "";
            }
            if(empty($row['meta_title'])) {
                $meta_title = $name_s;
            } else {
                $meta_title = $row['meta_title'];
            }
            if(empty($row['meta_description'])) {
                $meta_description = '';
            } else {
                $meta_description = $row['meta_description'];
            }
            if(empty($row['meta_image'])) {
                $meta_image = '';
            } else {
                $meta_image = $row['meta_image'];
            }
            $query_list = "SELECT v.id,s.lat,s.lon,s.initial_pos,v.code,v.author,v.name as title,v.description,v.background_image as image,r.panorama_image,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.hfov,COUNT(al.id) as total_access
                            FROM svt_globe_list as s
                            JOIN svt_virtualtours as v ON s.id_virtualtour=v.id
                            LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id AND r.id=(SELECT id FROM svt_rooms WHERE id_virtualtour=v.id ORDER BY priority LIMIT 1)
                            LEFT JOIN svt_access_log as al ON al.id_virtualtour=v.id
                            WHERE s.id_globe=$id_s AND v.active=1
                            GROUP BY v.id,s.lat,s.lon,s.initial_pos,v.code,v.author,v.name,v.description,v.background_image,r.panorama_image,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.hfov;";
            $result_list = $mysqli->query($query_list);
            if($result_list) {
                if($result_list->num_rows>0) {
                    while($row_list = $result_list->fetch_array(MYSQLI_ASSOC)) {
                        $id_vt = $row_list['id'];
                        $s3_params = check_s3_tour_enabled($id_vt);
                        $s3_enabled = false;
                        if(!empty($s3_params)) {
                            $s3_bucket_name = $s3_params['bucket'];
                            if($s3Client==null) {
                                $s3Client = init_s3_client_no_wrapper($s3_params);
                                if($s3Client==null) {
                                    $s3_enabled = false;
                                } else {
                                    if(!empty($s3_params['custom_domain'])) {
                                        $s3_url = "https://".$s3_params['custom_domain']."/";
                                    } else {
                                        try {
                                            $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                                        } catch (Aws\Exception\S3Exception $e) {}
                                    }
                                    $s3_enabled = true;
                                }
                            } else {
                                $s3_enabled = true;
                            }
                        }
                        if(empty($row_list['image'])) {
                            if(!empty($row_list['panorama_image'])) {
                                if($s3_enabled) {
                                    $row_list['image']=$s3_url.'viewer/panoramas/preview/'.$row_list['panorama_image'];
                                } else {
                                    $row_list['image']='../viewer/panoramas/preview/'.$row_list['panorama_image'];
                                }
                            }
                        } else {
                            if($s3_enabled) {
                                $row_list['image']=$s3_url.'viewer/content/'.$row_list['image'];
                            } else {
                                $row_list['image']='../viewer/content/'.$row_list['image'];
                            }
                        }
                        $row_list['s3'] = ($s3_enabled) ? 1 : 0;
                        $array_vt[] = $row_list;
                    }
                }
            } else {
                if(file_exists("../error_pages/custom/invalid_globe.html")) {
                    include("../error_pages/custom/invalid_globe.html");
                } else {
                    include("../error_pages/default/invalid_globe.html");
                }
                exit;
            }
        } else {
            if(file_exists("../error_pages/custom/invalid_globe.html")) {
                include("../error_pages/custom/invalid_globe.html");
            } else {
                include("../error_pages/default/invalid_globe.html");
            }
            exit;
        }
    } else {
        if(file_exists("../error_pages/custom/invalid_globe.html")) {
            include("../error_pages/custom/invalid_globe.html");
        } else {
            include("../error_pages/default/invalid_globe.html");
        }
        exit;
    }
} else {
    if(file_exists("../error_pages/custom/invalid_globe.html")) {
        include("../error_pages/custom/invalid_globe.html");
    } else {
        include("../error_pages/default/invalid_globe.html");
    }
    exit;
}

$globe_ion_token = "";
$globe_arcgis_token = "";
$globe_googlemaps_key = "";
$font_provider = "google";
$font_backend = "";
$cookie_policy = "";
$query = "SELECT globe_ion_token,globe_arcgis_token,globe_googlemaps_key,font_backend,font_provider,cookie_policy,theme_color FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $globe_ion_token = $row['globe_ion_token'];
        $globe_arcgis_token = $row['globe_arcgis_token'];
        $font_backend = $row['font_backend'];
        $font_provider = $row['font_provider'];
        $cookie_policy = $row['cookie_policy'];
        $theme_color = $row['theme_color'];
        if($globe_type=='google') {
            $globe_googlemaps_key = $row['globe_googlemaps_key'];
            if(empty($globe_googlemaps_key)) {
                $globe_type = 'default';
            }
        }
    }
}

$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace("/globe/","/",$url);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title><?php echo $meta_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta property="og:type" content="website">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="og:url" content="<?php echo $url."globe/index.php?code=".$code; ?>">
    <meta property="twitter:url" content="<?php echo $url."globe/index.php?code=".$code; ?>">
    <meta itemprop="name" content="<?php echo $meta_title; ?>">
    <meta property="og:title" content="<?php echo $meta_title; ?>">
    <meta property="twitter:title" content="<?php echo $meta_title; ?>">
    <?php if($meta_image!='') : ?>
        <meta itemprop="image" content="<?php echo $url."viewer/content/".$meta_image; ?>">
        <meta property="og:image" content="<?php echo $url."viewer/content/".$meta_image; ?>" />
        <meta property="twitter:image" content="<?php echo $url."viewer/content/".$meta_image; ?>">
    <?php endif; ?>
    <?php if($meta_description!='') : ?>
        <meta itemprop="description" content="<?php echo $meta_description; ?>">
        <meta name="description" content="<?php echo $meta_description; ?>"/>
        <meta property="og:description" content="<?php echo $meta_description; ?>" />
        <meta property="twitter:description" content="<?php echo $meta_description; ?>">
    <?php endif; ?>
    <?php echo print_favicons_globe($code,$logo_s,$theme_color); ?>
    <?php switch ($font_provider) {
    case 'google': ?>
        <?php if($cookie_consent) { ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <script type="text/plain" data-category="functionality" data-service="Google Fonts">
                (function(d, l, s) {
                    const fontName = '<?php echo $font_backend; ?>';
                    const e = d.createElement(l);
                    e.rel = s;
                    e.type = 'text/css';
                    e.href = `https://fonts.googleapis.com/css2?family=${fontName}`;
                    e.id = 'font_backend_link';
                    d.head.appendChild(e);
                  })(document, 'link', 'stylesheet');
            </script>
        <?php } else { ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel='stylesheet' type="text/css" crossorigin="anonymous" id="font_backend_link" href="https://fonts.googleapis.com/css2?family=<?php echo $font_backend; ?>">
        <?php } ?>
        <?php break;
        case 'collabs': ?>
        <link rel="preconnect" href="https://api.fonts.coollabs.io" crossorigin>
        <link rel="stylesheet" type="text/css" id="font_backend_link" href="https://api.fonts.coollabs.io/css2?family=<?php echo $font_backend; ?>&display=swap">
        <?php break;
        default: ?>
        <link rel="stylesheet" type="text/css" crossorigin="anonymous" id="font_backend_link" href="">
        <?php break;
    } ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/fontawesome.min.css?v=6.5.1">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/solid.min.css?v=6.5.1">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/regular.min.css?v=6.5.1">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/brands.min.css?v=6.5.1">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <?php if(empty($globe_ion_token)) { ?>
        <link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
    <?php } else { ?>
        <link href="https://cesium.com/downloads/cesiumjs/releases/1.113/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
    <?php } ?>
    <link rel="stylesheet" type='text/css' href="../viewer/css/pannellum.css"/>
    <?php if($cookie_consent) : ?>
        <link rel="stylesheet" type="text/css" href="../backend/vendor/cookieconsent/cookieconsent.min.css?v=3.0.1">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="css/index.css?v=<?php echo $v; ?>">
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_'.$code.'.css')) : ?>
        <link rel="stylesheet" type="text/css" href="css/custom_<?php echo $code; ?>.css?v=<?php echo $v; ?>">
    <?php endif; ?>
    <script type="text/javascript" src="js/jquery.min.js?v=3.7.1"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.bundle.min.js"></script>
    <?php if(empty($globe_ion_token)) { ?>
        <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <?php } else { ?>
        <script src="https://cesium.com/downloads/cesiumjs/releases/1.113/Build/Cesium/Cesium.js"></script>
    <?php } ?>
    <script type="text/javascript" src="../viewer/js/libpannellum.js?v=<?php echo $v; ?>"></script>
    <script type="text/javascript" src="../viewer/js/pannellum.js?v=<?php echo $v; ?>"></script>
    <?php if($cookie_consent) : ?>
        <script type="text/javascript" src="../backend/vendor/cookieconsent/cookieconsent.min.js?v=3.0.1"></script>
    <?php endif; ?>
</head>
<body>

<i id="loading">
    <svg width="150" height="150" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient x1="8.042%" y1="0%" x2="65.682%" y2="23.865%" id="a">
                <stop stop-color="#fff" stop-opacity="0" offset="0%"/>
                <stop stop-color="#fff" stop-opacity=".631" offset="63.146%"/>
                <stop stop-color="#fff" offset="100%"/>
            </linearGradient>
        </defs>
        <g fill="none" fill-rule="evenodd">
            <g transform="translate(1 1)">
                <path d="M36 18c0-9.94-8.06-18-18-18" id="Oval-2" stroke="url(#a)" stroke-width="2">
                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="0.9s" repeatCount="indefinite" />
                </path>
                <circle fill="#fff" cx="36" cy="18" r="1">
                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="0.9s" repeatCount="indefinite" />
                </circle>
            </g>
        </g>
    </svg>
</i>

<?php if(!empty($logo_s)) : ?>
    <div class="logo">
        <img draggable="false" src="../viewer/content/<?php echo $logo_s; ?>" />
    </div>
<?php endif; ?>

<div id="btn_return_globe"><img src="img/globe.png" /></div>
<div id="vt_viewer">
    <iframe referrerpolicy="origin" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src=""></iframe>
</div>
<div id="cesiumContainer"></div>

<?php foreach ($array_vt as $vt) { ?>
    <div id="vt_card_<?php echo $vt['id']; ?>" data-id="<?php echo $vt['id']; ?>" data-s3="<?php echo $vt['s3']; ?>" data-image="<?php echo $vt['image']; ?>" data-min_yaw="<?php echo $vt['min_yaw']; ?>" data-max_yaw="<?php echo $vt['max_yaw']; ?>" data-haov="<?php echo $vt['haov']; ?>" data-vaov="<?php echo $vt['vaov']; ?>" data-hfov="<?php echo $vt['hfov']; ?>" data-panorama="<?php echo $vt['panorama_image']; ?>" data-code="<?php echo $vt['code']; ?>" data-lat="<?php echo $vt['lat']; ?>" data-lon="<?php echo $vt['lon']; ?>" data-initial_pos="<?php echo $vt['initial_pos']; ?>" class="card vt-card">
        <div class="card-img-block noselect">
            <div id="panorama_preview_<?php echo $vt['id']; ?>" class="panorama_preview"></div>
            <?php if(empty($vt['image'])) { ?>
                <div style="height: 180px;background-color: darkgrey" class="card-img-top"></div>
            <?php } else { ?>
                <img draggable="false" class="card-img-top" src="<?php echo $vt['image']; ?>" alt="card image">
            <?php } ?>
            <div class="card-access noselect"><i class="far fa-eye"></i> <?php echo $vt['total_access']; ?></div>
        </div>
        <div class="card-body pt-0">
            <div class="row p-0">
                <div class="col-sm-6 col-6" style="padding: 0 10px;">
                    <button onclick="view_vt(<?php echo $vt['id']; ?>);" class="btn btn_view_vt btn-sm btn-block btn-outline-dark mb-3"><i class="fas fa-play"></i></button>
                </div>
                <div class="col-sm-6 col-6" style="padding: 0 10px;">
                    <button onclick="flyto_vt(<?php echo $vt['id']; ?>,false,false);" class="btn btn_fly_vt btn-sm btn-block btn-outline-dark mb-3"><i class="fas fa-crosshairs"></i></button>
                </div>
            </div>
            <h5 class="card-title noselect"><?php echo $vt['title']; ?></h5>
            <p class="card-author noselect"><?php echo $vt['author']; ?></p>
            <p class="card-text noselect"><?php echo $vt['description']; ?></p>
        </div>
    </div>
<?php } ?>

<?php if($cookie_consent) : ?>
    <div data-cc="show-consentModal" id="cookie_consent_preferences"><i class="fa-solid fa-cookie-bite"></i><span>&nbsp;&nbsp;<?php echo _("Cookie Preferences"); ?></span></div>
<?php endif; ?>

<?php if(!empty($cookie_policy) && $cookie_policy!='<p></p>') : ?>
    <div id="modal_cookie_policy_b" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Cookie Policy"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $cookie_policy; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    window.open_target = '<?php echo $open_target; ?>';
    var set_initial_pos_c = <?php echo $set_initial_pos; ?>;
    var initial_pos = '<?php echo $initial_pos; ?>';
    var id_tour = <?php echo $id_tour; ?>;
    $(document).ready(function() {
        window.viewer = null;
        window.viewer_initialized = false;
        window.array_entity = [];
        window.scratch3dPosition = new Cesium.Cartesian3();
        window.scratch2dPosition = new Cesium.Cartesian2();
        window.pointer_size = <?php echo $pointer_size; ?>;
        window.pointer_color = '<?php echo $pointer_color; ?>';
        window.pointer_border = '<?php echo $pointer_border; ?>';
        var drag_p = false, start_drag, end_drag;
        window.open_vt_card = false;
        window.id_open_vt_card = 0;
        window.current_height = 0;
        window.center_altitude = 0;
        window.center_heading = 0;
        window.center_pitch = -90;
        window.center_roll = -90;
        window.min_altitude = <?php echo $min_altitude; ?>;
        window.center_lat = '';
        window.center_lon = '';
        window.zoom_duration = <?php echo $zoom_duration; ?>;
        window.default_view = '<?php echo $default_view; ?>';
        window.s3_url = '<?php echo $s3_url; ?>';
        var globe_type = '<?php echo $globe_type; ?>';
        <?php if(!empty($globe_ion_token)) : ?>
        Cesium.Ion.defaultAccessToken = '<?php echo $globe_ion_token; ?>';
        Cesium.ArcGisMapService.defaultAccessToken = '<?php echo $globe_arcgis_token; ?>';
        Cesium.GoogleMaps.defaultApiKey = '<?php echo $globe_googlemaps_key; ?>';
        <?php endif; ?>

        $("#btn_return_globe").draggable({
            containment: "#cesiumContainer",
            start: function( event, ui ) {
                $('#vt_viewer').css('pointer-events','none');
                $(this).addClass('dragging');
            },
            stop: function( event, ui ) {
                $('#vt_viewer').css('pointer-events','initial');
            },
        });

        $('#btn_return_globe').click(function (event) {
            if ($(this).parent().hasClass('dragging')) {
                $(this).parent().removeClass('dragging');
            } else {
                return_globe();
            }
        });

        var imageryProviders = Cesium.createDefaultImageryProviderViewModels();
        var imageryProviders_o = [];
        imageryProviders_o.push(imageryProviders[3]);
        imageryProviders_o.push(imageryProviders[6]);

        switch(window.default_view) {
            case 'satellite':
                var imagery_provider = imageryProviders_o[0];
                break;
            case 'street':
                var imagery_provider = imageryProviders_o[1];
                break;
        }

        switch(globe_type) {
            case 'google':
                var google_tilest = null;
                viewer = new Cesium.Viewer('cesiumContainer', {
                    homeButton: (set_initial_pos_c==1) ? false : true,
                    baseLayerPicker: false,
                    geocoder: false,
                    animation: false,
                    timeline: false,
                    fullscreenButton: false,
                    selectionIndicator: false,
                    infoBox: false,
                    sceneModePicker: false,
                    terrain: Cesium.Terrain.fromWorldTerrain()
                });
                //viewer.scene.globe.show = false;
                async function init_google_tiles() {
                    try {
                        google_tilest = await Cesium.createGooglePhotorealistic3DTileset();
                        viewer.scene.primitives.add(google_tilest);
                    } catch (error) {
                        console.log(`Failed to load tileset: `+error);
                    }
                    if(!viewer_initialized) {
                        init_globe(google_tilest);
                    }
                }
                init_google_tiles();
                break;
            default:
                viewer = new Cesium.Viewer('cesiumContainer', {
                    imageryProviderViewModels: imageryProviders_o,
                    selectedImageryProviderViewModel: imagery_provider,
                    terrainProviderViewModels: [],
                    homeButton: (set_initial_pos_c==1) ? false : true,
                    baseLayerPicker: true,
                    geocoder: false,
                    animation: false,
                    timeline: false,
                    fullscreenButton: false,
                    selectionIndicator: false,
                    infoBox: false,
                    sceneModePicker: false
                });

                if(set_initial_pos_c==0) {
                    Cesium.subscribeAndEvaluate(viewer.baseLayerPicker.viewModel, 'selectedImagery', function(newValue) {
                        if(newValue.name=='Open­Street­Map') {
                            viewer.scene.skyAtmosphere.show = false;
                            viewer.scene.fog.enabled = false;
                            viewer.scene.globe.showGroundAtmosphere = false;
                        } else {
                            viewer.scene.skyAtmosphere.show = true;
                            viewer.scene.fog.enabled = true;
                            viewer.scene.globe.showGroundAtmosphere = true;
                        }
                    });
                }

                break;
        }

        if(window.min_altitude!=0) {
            viewer.scene.screenSpaceCameraController.minimumZoomDistance = window.min_altitude*1000;
        }

        window.cartographic = new Cesium.Cartographic();
        window.cartesian = new Cesium.Cartesian3();
        window.camera = viewer.scene.camera;
        window.ellipsoid = viewer.scene.mapProjection.ellipsoid;

        switch (globe_type) {
            case 'google':
                break;
            default:
                viewer.scene.globe.tileLoadProgressEvent.addEventListener(function (queuedTileCount) {
                    if(viewer.scene.globe.tilesLoaded && !viewer_initialized) {
                        init_globe(null);
                    }
                });
                break;
        }
    });

    function init_globe(google_tileset=null) {
        viewer_initialized = true;

        var initial_pos_tour = '';
        var lat_pos_tour = 0;
        var lon_pos_tour = 0;
        var array_coords = [];
        $('.vt-card').each(function () {
            var id_vt = $(this).attr('data-id');
            var lat = $(this).attr('data-lat');
            var lon = $(this).attr('data-lon');
            var initial_pos = $(this).attr('data-initial_pos');
            if(parseInt(id_vt)==parseInt(id_tour)) {
                initial_pos_tour = initial_pos;
                lat_pos_tour = lat;
                lon_pos_tour = lon;
            }
            var entity = viewer.entities.add({
                id: id_vt,
                type: 'vt',
                position: Cesium.Cartesian3.fromDegrees(lon, lat),
                point: {
                    show: true,
                    color: Cesium.Color.fromCssColorString(pointer_color),
                    pixelSize: pointer_size,
                    outlineColor: Cesium.Color.fromCssColorString(pointer_border),
                    outlineWidth: (pointer_size/10)
                },
            });
            if(google_tileset!=null) {
                setInterval(function () {
                    var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height/300;
                    if(altitude!=null) {
                        var cartesianPosition = viewer.scene.clampToHeight(Cesium.Cartesian3.fromDegrees(lon, lat), [entity]);
                        var cartographicPosition = Cesium.Cartographic.fromCartesian(cartesianPosition);
                        cartographicPosition.height += altitude;
                        var shiftedCartesianPosition = viewer.scene.globe.ellipsoid.cartographicToCartesian(cartographicPosition);
                        entity.position = shiftedCartesianPosition;
                    }
                },250);
            }
            var tmp = [];
            tmp[0]=lat;
            tmp[1]=lon;
            array_coords.push(tmp);
            array_entity[id_vt] = entity;
        });

        if(id_tour!=0) {
            if(initial_pos_tour!='') {
                var tmp_initial_pos = initial_pos_tour.split(",");
                window.center_lon = parseFloat(tmp_initial_pos[0]);
                window.center_lat = parseFloat(tmp_initial_pos[1]);
                window.center_altitude = parseFloat(tmp_initial_pos[2]);
                window.center_heading = parseFloat(tmp_initial_pos[3]);
                window.center_pitch = parseFloat(tmp_initial_pos[4]);
                window.center_roll = parseFloat(tmp_initial_pos[5]);
                if(window.center_altitude!=0) {
                    var altitude = window.center_altitude;
                } else {
                    var altitude = window.min_altitude*1000;
                }
                if(window.min_altitude!=0) {
                    if(altitude<(window.min_altitude*1000)) {
                        altitude = window.min_altitude*1000;
                    }
                }
                viewer.camera.flyTo({
                    destination: Cesium.Cartesian3.fromDegrees(window.center_lon, window.center_lat, altitude),
                    duration: 0.1,
                    orientation: {
                        pitch: Cesium.Math.toRadians(window.center_pitch),
                        heading: Cesium.Math.toRadians(window.center_heading),
                        roll: Cesium.Math.toRadians(window.center_roll)
                    }
                });
            } else {
                var altitude = 2000;
                viewer.camera.flyTo({
                    destination: Cesium.Cartesian3.fromDegrees(lon_pos_tour, lat_pos_tour, altitude),
                    duration: 0.1,
                });
            }
        } else {
            if(initial_pos!='') {
                var tmp_initial_pos = initial_pos.split(",");
                window.center_lon = parseFloat(tmp_initial_pos[0]);
                window.center_lat = parseFloat(tmp_initial_pos[1]);
                window.center_altitude = parseFloat(tmp_initial_pos[2]);
                window.center_heading = parseFloat(tmp_initial_pos[3]);
                window.center_pitch = parseFloat(tmp_initial_pos[4]);
                window.center_roll = parseFloat(tmp_initial_pos[5]);
                if(window.center_altitude!=0) {
                    var altitude = window.center_altitude;
                } else {
                    var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height;
                }
                if(window.min_altitude!=0) {
                    if(altitude<(window.min_altitude*1000)) {
                        altitude = window.min_altitude*1000;
                    }
                }
                viewer.camera.flyTo({
                    destination: Cesium.Cartesian3.fromDegrees(window.center_lon, window.center_lat, altitude),
                    duration: (set_initial_pos_c==0) ? 1 : 0,
                    orientation: {
                        pitch: Cesium.Math.toRadians(window.center_pitch),
                        heading: Cesium.Math.toRadians(window.center_heading),
                        roll: Cesium.Math.toRadians(window.center_roll)
                    }
                });
                if(set_initial_pos_c==0) {
                    viewer.homeButton.viewModel.command.beforeExecute.addEventListener(
                        function(e) {
                            e.cancel = true;
                            viewer.camera.flyTo({
                                destination: Cesium.Cartesian3.fromDegrees(window.center_lon, window.center_lat, altitude),
                                orientation: {
                                    pitch: Cesium.Math.toRadians(window.center_pitch),
                                    heading: Cesium.Math.toRadians(window.center_heading),
                                    roll: Cesium.Math.toRadians(window.center_roll)
                                }
                            });
                        }
                    );
                }
            } else {
                if(array_coords.length>0) {
                    var center = getLatLngCenter(array_coords);
                    var center_lat = center[0];
                    var center_lon = center[1];
                    if(window.center_altitude!=0) {
                        var altitude = window.center_altitude;
                    } else {
                        var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height;
                    }
                    if(altitude<(window.min_altitude*1000)) {
                        altitude = window.min_altitude*1000;
                    }
                    viewer.camera.flyTo({
                        destination: Cesium.Cartesian3.fromDegrees(center_lon, center_lat, altitude),
                        duration: (set_initial_pos_c==0) ? 1 : 0.1
                    });
                    if(set_initial_pos_c==0) {
                        viewer.homeButton.viewModel.command.beforeExecute.addEventListener(
                            function(e) {
                                e.cancel = true;
                                viewer.camera.flyTo({
                                    destination: Cesium.Cartesian3.fromDegrees(center_lon, center_lat, altitude)
                                });
                            }
                        );
                    }
                }
            }
        }

        if(set_initial_pos_c==0) {
            const handler = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
            viewer.screenSpaceEventHandler.removeInputAction(Cesium.ScreenSpaceEventType.LEFT_DOUBLE_CLICK);
            handler.setInputAction(function (movement) {
                if(open_vt_card) {
                    start_drag = new Date().getTime();
                    drag_p = false;
                }
            }, Cesium.ScreenSpaceEventType.LEFT_DOWN);
            handler.setInputAction(function (movement) {
                if(open_vt_card) {
                    end_drag = new Date().getTime();
                    drag_p = true;
                }
                jQuery.each(array_entity, function(id_t, entity_t) {
                    if(entity_t!==undefined) {
                        if (entity_t.hasOwnProperty('_point')) {
                            entity_t.point.pixelSize = pointer_size;
                        }
                    }
                });
                document.getElementById('cesiumContainer').style.cursor = 'default';
                const pickedObject = viewer.scene.pick(movement.endPosition);
                if (Cesium.defined(pickedObject)) {
                    if(pickedObject.id!==undefined) {
                        if (pickedObject.id.hasOwnProperty('type')) {
                            var id = pickedObject.id._id;
                            array_entity[id].point.pixelSize = pointer_size*1.3;
                            document.getElementById('cesiumContainer').style.cursor = 'pointer';
                        }
                    }
                }
            }, Cesium.ScreenSpaceEventType.MOUSE_MOVE);
            handler.setInputAction(function (movement) {
                if(open_vt_card) {
                    var diff_drag = end_drag - start_drag;
                    if(drag_p == false || diff_drag < 100) {
                        $('.vt-card').hide();
                        $('.vt-card').css('opacity',0);
                        open_vt_card = false;
                        id_open_vt_card = 0;
                        viewer.selectedEntity = undefined;
                    }
                }
            }, Cesium.ScreenSpaceEventType.LEFT_UP);

            viewer.selectedEntityChanged.addEventListener(function(entity) {
                if(entity!==undefined) {
                    if(entity.hasOwnProperty('type')) {
                        if(!open_vt_card) {
                            var id = entity.id;
                            id_open_vt_card = id;
                            $('#vt_card_'+id).show();
                            var image_sel = $('#vt_card_'+id).attr('data-image');
                            if(!image_sel.includes('preview')) {
                                $('#vt_viewer').css('background-image','url('+image_sel+')');
                            } else {
                                $('#vt_viewer').css('background-image','none');
                            }
                            setTimeout(function () {
                                open_vt_card = true;
                            },50);
                            viewer.selectedEntity = undefined;
                        } else {
                            var id = entity.id;
                            if(id_open_vt_card!=id) {
                                setTimeout(function () {
                                    id_open_vt_card = id;
                                    $('#vt_card_'+id).show();
                                    open_vt_card = true;
                                    viewer.selectedEntity = undefined;
                                },50);
                            }
                        }
                        /*var lat = parseFloat($('#vt_card_'+id).attr('data-lat'));
                        var lon = parseFloat($('#vt_card_'+id).attr('data-lon'));
                        var dest_coord = Cesium.Cartesian3.fromDegrees(lon, lat, window.current_height);
                        viewer.camera.flyTo({
                            destination: dest_coord,
                            duration: 0.5,
                        });*/
                    }
                }
            });

            viewer.clock.onTick.addEventListener(function(clock) {
                ellipsoid.cartesianToCartographic(camera.positionWC, cartographic);
                window.current_height = cartographic.height;
                if (cartographic.height>5000) {
                    $('.btn_fly_vt').removeClass('disabled');
                } else {
                    $('.btn_fly_vt').addClass('disabled');
                }
                if(open_vt_card && id_open_vt_card!=0) {
                    var position3d;
                    var position2d;
                    var vt_card = $('#vt_card_'+id_open_vt_card);
                    var entity = array_entity[id_open_vt_card];
                    if (entity.position) {
                        position3d = entity.position.getValue(clock.currentTime, scratch3dPosition);
                    }
                    if (position3d) {
                        position2d = Cesium.SceneTransforms.wgs84ToWindowCoordinates(
                            viewer.scene, position3d, scratch2dPosition);
                    }
                    if (position2d) {
                        vt_card.css('right',(window.innerWidth - position2d.x) + 'px');
                        vt_card.css('bottom',(window.innerHeight - position2d.y) + (pointer_size+10) + 'px');
                        vt_card.css('opacity',1);
                    }
                }
            });
        }
        setTimeout(function () {
            $('#loading').fadeOut();
        },250);
    }

    window.flyto_vt = function(id_vt,duration,view_mode) {
        var lat = $('#vt_card_'+id_vt).attr('data-lat');
        var lon = $('#vt_card_'+id_vt).attr('data-lon');
        var initial_pos = $('#vt_card_'+id_vt).attr('data-initial_pos');
        $('#vt_card_'+id_vt).css('display','none');
        if(duration==false) {
            if(window.current_height>5000) {
                duration = window.zoom_duration;
            } else {
                duration = 500;
            }
        }
        if(initial_pos!='') {
            var tmp_initial_pos = initial_pos.split(",");
            lon = parseFloat(tmp_initial_pos[0]);
            lat = parseFloat(tmp_initial_pos[1]);
            var altitude = parseFloat(tmp_initial_pos[2]);
            var heading = parseFloat(tmp_initial_pos[3]);
            var pitch = parseFloat(tmp_initial_pos[4]);
            var roll = parseFloat(tmp_initial_pos[5]);
            if(window.min_altitude!=0) {
                if(altitude<(window.min_altitude*1000)) {
                    altitude = window.min_altitude*1000;
                }
            }
            viewer.camera.flyTo({
                destination: Cesium.Cartesian3.fromDegrees(lon, lat, altitude),
                duration: (duration/1000),
                orientation: {
                    pitch: Cesium.Math.toRadians(pitch),
                    heading: Cesium.Math.toRadians(heading),
                    roll: Cesium.Math.toRadians(roll)
                }
            });
        } else {
            if((window.min_altitude*1000)<2000) {
                var altitude = 2000;
            } else {
                var altitude = window.min_altitude*1000;
            }
            viewer.camera.flyTo({
                destination: Cesium.Cartesian3.fromDegrees(lon, lat, altitude),
                duration: (duration/1000),
            });
        }
        if(!view_mode) {
            setTimeout(function () {
                $('#vt_card_'+id_vt).css('display','block');
            },duration);
        }
    }

    window.view_vt = function (id_vt) {
        var vt_code = $('#vt_card_'+id_vt).attr('data-code');
        if(window.open_target=='new') {
            window.open('../viewer/index.php?code='+vt_code,'_blank');
        } else {
            if(window.current_height>5000) {
                var duration = window.zoom_duration;
                var iframe_load = duration-1000;
            } else {
                var duration = 500;
                var iframe_load = 0;
            }
            flyto_vt(id_vt,duration,true);
            setTimeout(function() {
                $('#vt_viewer iframe').attr('src','../viewer/index.php?code='+vt_code+'&ignore_embedded=1');
            },iframe_load);
            $('.vt-card').hide();
            $('.vt-card').css('opacity',0);
            open_vt_card = false;
            id_open_vt_card = 0;
            viewer.selectedEntity = undefined;
            setTimeout(function () {
                $('#vt_viewer').fadeIn(200);
                $('#btn_return_globe').fadeIn(200);
                $('#vt_viewer').css('z-index',10);
                $('#btn_return_globe').css('z-index',11);
                $('#vt_viewer iframe').css('opacity',1);
            },duration);
        }
    }

    window.return_globe = function () {
        $('.cesium-home-button').trigger('click');
        $('#vt_viewer').fadeOut(200);
        $('#btn_return_globe').hide();
        setTimeout(function () {
            $('#vt_viewer iframe').attr('src','about:blank');
            $('#vt_viewer iframe').css('opacity',0);
            $('#vt_viewer').css('z-index',0);
            $('#btn_return_globe').css('z-index',0);
        },200);
    }

    var panorama_preview = null, timeout_destroy;
    function initialize_panorama_preview(id,image,s3,min_yaw,max_yaw,haov,vaov,hfov) {
        if(hfov==0) { hfov=90; } else { hfov=hfov*0.8; }
        try {
            panorama_preview.destroy();
        } catch (e) {}
        panorama_preview = pannellum.viewer('panorama_preview_'+id, {
            "type": "equirectangular",
            "autoLoad": true,
            "autoRotate": -20,
            "showControls": false,
            "compass": false,
            "minYaw": parseInt(min_yaw),
            "maxYaw": parseInt(max_yaw),
            "haov": parseInt(haov),
            "vaov": parseInt(vaov),
            "hfov": parseInt(hfov),
            "panorama": (s3==1) ? window.s3_url+"viewer/panoramas/lowres/"+image : "../viewer/panoramas/lowres/"+image
        });
        panorama_preview.on('load',function () {
            setTimeout(function () {
                $('#panorama_preview_'+id).css('opacity',1);
            },50);
        });
        $('.panorama_preview').css('opacity',0);
    }

    $('.vt-card').on('mouseenter', function () {
        var id = $(this).attr('data-id');
        var image = $(this).attr('data-panorama');
        if(image!='') {
            var s3 = parseInt($(this).attr('data-s3'));
            var min_yaw = $(this).attr('data-min_yaw');
            var max_yaw = $(this).attr('data-max_yaw');
            var haov = $(this).attr('data-haov');
            var vaov = $(this).attr('data-vaov');
            var hfov = $(this).attr('data-hfov');
            clearTimeout(timeout_destroy);
            initialize_panorama_preview(id,image,s3,min_yaw,max_yaw,haov,vaov,hfov);
        }
    });

    $('.vt-card').on('mouseleave', function () {
        $('.panorama_preview').css('opacity',0);
        timeout_destroy = setTimeout(function() {
            try {
                panorama_preview.destroy();
            } catch (e) {}
        },300);
    });

    function rad2degr(rad) { return rad * 180 / Math.PI; }
    function degr2rad(degr) { return degr * Math.PI / 180; }

    function getLatLngCenter(latLngInDegr) {
        var LATIDX = 0;
        var LNGIDX = 1;
        var sumX = 0;
        var sumY = 0;
        var sumZ = 0;
        for (var i=0; i<latLngInDegr.length; i++) {
            var lat = degr2rad(latLngInDegr[i][LATIDX]);
            var lng = degr2rad(latLngInDegr[i][LNGIDX]);
            sumX += Math.cos(lat) * Math.cos(lng);
            sumY += Math.cos(lat) * Math.sin(lng);
            sumZ += Math.sin(lat);
        }
        var avgX = sumX / latLngInDegr.length;
        var avgY = sumY / latLngInDegr.length;
        var avgZ = sumZ / latLngInDegr.length;
        var lng = Math.atan2(avgY, avgX);
        var hyp = Math.sqrt(avgX * avgX + avgY * avgY);
        var lat = Math.atan2(avgZ, hyp);
        return ([rad2degr(lat), rad2degr(lng)]);
    }

    window.get_camera_view_position = function() {
        const position = viewer.camera.positionCartographic;
        var hpr = `,${Cesium.Math.toDegrees(viewer.camera.heading)},${Cesium.Math.toDegrees(viewer.camera.pitch)},${Cesium.Math.toDegrees(viewer.camera.roll)}`;
        var camera_view = `${Cesium.Math.toDegrees(position.longitude)},${Cesium.Math.toDegrees(position.latitude)},${position.height}${hpr}`;
        return camera_view;
    }

</script>
<?php if(!empty($ga_tracking_id)) : ?>
    <?php if($cookie_consent) { ?>
        <script type="text/plain" data-category="analytics" data-service="Google Analytics" async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_tracking_id; ?>"></script>
        <script type="text/plain" data-category="analytics" data-service="Google Analytics">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $ga_tracking_id; ?>');
        </script>
    <?php } else { ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_tracking_id; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $ga_tracking_id; ?>');
        </script>
    <?php } ?>
<?php endif; ?>
<?php if($cookie_consent) : ?>
    <?php require_once('cookie_consent.php'); ?>
<?php endif; ?>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js', {
            scope: '.'
        });
    }
</script>
</body>
</html>

<?php
function print_favicons_globe($code,$logo,$theme_color) {
    $path = '';
    $path_m = 'g_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/../favicons/g_'.$code.'/favicon.ico')) {
        $path = 'g_'.$code.'/';
    } else if (file_exists(dirname(__FILE__).'/../favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    $version = preg_replace('/[^0-9]/', '', $logo);
    return '<link rel="apple-touch-icon" sizes="180x180" href="../favicons/'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicons/'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicons/'.$path.'favicon-16x16.png?v='.$version.'">
    <link rel="manifest" href="../favicons/'.$path_m.'site.webmanifest?v='.$version.'">
    <link rel="mask-icon" href="../favicons/'.$path.'safari-pinned-tab.svg?v='.$version.'" color="'.$theme_color.'">
    <link rel="shortcut icon" href="../favicons/'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="'.$theme_color.'">
    <meta name="msapplication-config" content="../favicons/'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="'.$theme_color.'">';
}
?>
