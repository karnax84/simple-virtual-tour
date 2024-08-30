<?php
session_start();
$settings = get_settings();
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
$virtual_tour = get_virtual_tour($id_virtualtour_sel,$id_user);
$tmp_languages = get_languages_vt();
$array_languages = $tmp_languages[0];
$default_language = $tmp_languages[1];
if($user_info['role']=='editor') {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour_sel);
    if($editor_permissions['translate']==0) {
        $virtual_tour=false;
    }
}
$deepl_api_key = $settings['deepl_api_key'];
$enable_deepl = $settings['enable_deepl'];
if($enable_deepl && !empty($deepl_api_key)) {
    $deepl = 1;
} else {
    $deepl = 0;
}
?>

<?php if(!$virtual_tour): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<?php if($virtual_tour['external']==1) : ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("You cannot bulk translate an external virtual tour!"); ?>
        </div>
    </div>
<?php die(); endif; ?>

<?php if(count($array_languages) < 2): ?>
    <div class="card bg-warning text-white shadow mb-4">
        <div class="card-body">
            <?php echo _("The tour is not multi-language! To enable other languages go to the UI Editor."); ?>
        </div>
    </div>
<?php die(); endif; ?>

<style>
    <?php if($demo): ?>
        .block_translate {
            pointer-events: none !important;
        }
    <?php endif; ?>
</style>

<ul class="nav bg-white nav-pills nav-fill mb-2">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="pill" href="#virtualtour_tab"><i class="fas fa-route"></i> <?php echo strtoupper(_("Virtual Tour")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#rooms_tab"><i class="fas fa-vector-square"></i> <?php echo strtoupper(_("Rooms")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#rooms_alt_tab"><i class="fas fa-columns"></i> <?php echo strtoupper(_("Multiple Room Views")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#maps_tab"><i class="fas fa-map-marked-alt"></i> <?php echo strtoupper(_("Maps")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#gallery_tab"><i class="fas fa-images"></i> <?php echo strtoupper(_("Gallery")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#presentation_tab"><i class="fas fa-directions"></i> <?php echo strtoupper(_("Presentation")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#products_tab"><i class="fas fa-shopping-cart"></i> <?php echo strtoupper(_("Products")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#markers_tab"><i class="fas fa-caret-square-up"></i> <?php echo strtoupper(_("Markers")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="pill" href="#pois_tab"><i class="fas fa-bullseye"></i> <?php echo strtoupper(_("POIs")); ?></a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="virtualtour_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_virtualtours_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        if(!empty($virtual_tour['form_content'])) {
                            $virtual_tour['form_content'] = json_decode($virtual_tour['form_content'],true);
                        }
                        $array_input_lang = array();
                        $query_lang = "SELECT * FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour_sel;";
                        $result_lang = $mysqli->query($query_lang);
                        if($result_lang) {
                            if ($result_lang->num_rows > 0) {
                                while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                    $language = $row_lang['language'];
                                    if(!empty($row_lang['form_content'])) {
                                        $row_lang['form_content']=json_decode($row_lang['form_content'],true);
                                    } else {
                                        $row_lang['form_content']=array();
                                    }
                                    unset($row_lang['id_virtualtour']);
                                    unset($row_lang['language']);
                                    $array_input_lang[$language]=$row_lang;
                                }
                            }
                        }
                        echo print_value_input('name',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('description',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('meta_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('meta_description',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('password_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('password_description',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input_form('form_content',0,'title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang');
                        echo print_value_input_form('form_content',0,'button',$virtual_tour,$array_input_lang,'svt_virtualtours_lang');
                        echo print_value_input_form('form_content',0,'response',$virtual_tour,$array_input_lang,'svt_virtualtours_lang');
                        echo print_value_input_form('form_content',0,'description',$virtual_tour,$array_input_lang,'svt_virtualtours_lang');
                        for($i=1;$i<=10;$i++) {
                            echo print_value_input_form('form_content',$i,'label',$virtual_tour,$array_input_lang,'svt_virtualtours_lang');
                        }
                        echo print_value_input('media_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('location_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('custom_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('custom2_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('custom3_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('custom4_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        echo print_value_input('custom5_title',$virtual_tour,$array_input_lang,'svt_virtualtours_lang','text');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="rooms_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_rooms_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_sel;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_room = $row['id'];
                                    $room = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_rooms_lang WHERE id_room=$id_room;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('name',$room,$array_input_lang,'svt_rooms_lang','text');
                                    echo print_value_input('annotation_title',$room,$array_input_lang,'svt_rooms_lang','text');
                                    echo print_value_input('annotation_description',$room,$array_input_lang,'svt_rooms_lang','textarea');
                                    echo print_value_input('passcode_title',$room,$array_input_lang,'svt_rooms_lang','text');
                                    echo print_value_input('passcode_description',$room,$array_input_lang,'svt_rooms_lang','text');
                                    echo print_value_input('main_view_tooltip',$room,$array_input_lang,'svt_rooms_lang','text');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="rooms_alt_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_rooms_alt_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_sel);";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_room_alt = $row['id'];
                                    $room_alt = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_rooms_alt_lang WHERE id_room_alt=$id_room_alt;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room_alt']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('view_tooltip',$room_alt,$array_input_lang,'svt_rooms_alt_lang','text');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="maps_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_maps_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_maps WHERE id_virtualtour=$id_virtualtour_sel;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_map = $row['id'];
                                    $map = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_maps_lang WHERE id_map=$id_map;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room_alt']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('name',$map,$array_input_lang,'svt_maps_lang','text');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="gallery_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_gallery_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_gallery WHERE id_virtualtour=$id_virtualtour_sel;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_gallery = $row['id'];
                                    $gallery = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_gallery_lang WHERE id_gallery=$id_gallery;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room_alt']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('title',$gallery,$array_input_lang,'svt_gallery_lang','text');
                                    echo print_value_input('description',$gallery,$array_input_lang,'svt_gallery_lang','textarea');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="presentation_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_presentations_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_presentations WHERE action='type' AND id_virtualtour=$id_virtualtour_sel;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_presentation = $row['id'];
                                    $presentation = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_presentations_lang WHERE id_presentation=$id_presentation;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room_alt']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('params',$presentation,$array_input_lang,'svt_presentations_lang','textarea');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="products_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_products_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_products WHERE id_virtualtour=$id_virtualtour_sel;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_product = $row['id'];
                                    $product = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_products_lang WHERE id_product=$id_product;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_room_alt']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('name',$product,$array_input_lang,'svt_products_lang','text');
                                    echo print_value_input('description',$product,$array_input_lang,'svt_products_lang','editor');
                                    echo print_value_input('button_text',$product,$array_input_lang,'svt_products_lang','text');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="markers_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_markers_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_markers WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_sel);";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_marker = $row['id'];
                                    $marker = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_markers_lang WHERE id_marker=$id_marker;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                unset($row_lang['id_marker']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('label',$marker,$array_input_lang,'svt_markers_lang','text');
                                    echo print_value_input('tooltip_text',$marker,$array_input_lang,'svt_markers_lang','editor');
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="pois_tab">
        <div class="row">
            <div class="col-md-12">
                <div data-table="svt_pois_lang" class="card block_translate shadow mb-4">
                    <div class="card-header py-3">
                        <i class="fas fa-circle icon_check_translate"></i>&nbsp;&nbsp;<h6 class="m-0 font-weight-bold d-inline-block text-primary"><?php echo sprintf(_("%s of %s translated"),'<span class="count_translated">--</span>','<span class="count_total_translate">--</span>') ?></h6>
                        <?php if($deepl==1) : ?>
                            <button class="btn_bulk_translate btn btn-sm btn-primary ml-3 disabled <?php echo ($demo) ? 'disabled_d' : ''; ?>"><?php echo _("translate missing values"); ?>&nbsp;&nbsp;<i style="vertical-align:middle" class="fas fa-globe"></i></button>
                        <?php endif; ?>
                        <label class="ml-3"><input class="checkbox_only_translated" type="checkbox" /> <?php echo _("view only not translated"); ?></label>
                        <div class="float-right d-inline-block grid_list_switcher">
                            <i onclick="change_translate_view('list');" class="fas fa-table-list"></i>&nbsp;&nbsp;<i onclick="change_translate_view('grid');" class="fas fa-table-cells text-primary active"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_sel);";
                        $result = $mysqli->query($query);
                        if($result) {
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                    $id_poi = $row['id'];
                                    if($row['type']=='form' && !empty($row['content'])) {
                                        $row['content'] = json_decode($row['content'],true);
                                    }
                                    if($row['type']=='callout' && !empty($row['params'])) {
                                        $row['params'] = json_decode($row['params'],true);
                                    }
                                    $poi = $row;
                                    $array_input_lang = array();
                                    $query_lang = "SELECT * FROM svt_pois_lang WHERE id_poi=$id_poi;";
                                    $result_lang = $mysqli->query($query_lang);
                                    if($result_lang) {
                                        if ($result_lang->num_rows > 0) {
                                            while($row_lang = $result_lang->fetch_array(MYSQLI_ASSOC)) {
                                                $language = $row_lang['language'];
                                                if($row['type']=='form' && !empty($row_lang['content'])) {
                                                    $row_lang['content'] = json_decode($row_lang['content'],true);
                                                }
                                                if($row['type']=='callout' && !empty($row_lang['params'])) {
                                                    $row_lang['params'] = json_decode($row_lang['params'],true);
                                                }
                                                unset($row_lang['id_poi']);
                                                unset($row_lang['language']);
                                                $array_input_lang[$language]=$row_lang;
                                            }
                                        }
                                    }
                                    echo print_value_input('label',$poi,$array_input_lang,'svt_pois_lang','text');
                                    echo print_value_input('tooltip_text',$poi,$array_input_lang,'svt_pois_lang','editor');
                                    echo print_value_input('title',$poi,$array_input_lang,'svt_pois_lang','text');
                                    echo print_value_input('description',$poi,$array_input_lang,'svt_pois_lang','text');
                                    switch($row['type']) {
                                        case 'html':
                                            echo print_value_input('content',$poi,$array_input_lang,'svt_pois_lang','editor');
                                            break;
                                        case 'form':
                                            echo print_value_input_form('content',0,'title',$poi,$array_input_lang,'svt_pois_lang');
                                            echo print_value_input_form('content',0,'button',$poi,$array_input_lang,'svt_pois_lang');
                                            echo print_value_input_form('content',0,'response',$poi,$array_input_lang,'svt_pois_lang');
                                            echo print_value_input_form('content',0,'description',$poi,$array_input_lang,'svt_pois_lang');
                                            for($i=1;$i<=10;$i++) {
                                                echo print_value_input_form('content',$i,'label',$poi,$array_input_lang,'svt_pois_lang');
                                            }
                                            break;
                                        case 'callout':
                                            echo print_value_input_callout('params','title',$poi,$array_input_lang,'svt_pois_lang');
                                            echo print_value_input_callout('params','description',$poi,$array_input_lang,'svt_pois_lang');
                                            break;
                                    }
                                    switch($row['embed_type']) {
                                        case 'text':
                                            echo print_value_input('embed_content',$poi,$array_input_lang,'svt_pois_lang','editor');
                                            break;
                                    }
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        window.id_virtualtour = <?php echo $id_virtualtour_sel; ?>;
        var timeout_input = null;
        var DirectionAttribute = Quill.import('attributors/attribute/direction');
        Quill.register(DirectionAttribute,true);
        var AlignClass = Quill.import('attributors/class/align');
        Quill.register(AlignClass,true);
        var BackgroundClass = Quill.import('attributors/class/background');
        Quill.register(BackgroundClass,true);
        var ColorClass = Quill.import('attributors/class/color');
        Quill.register(ColorClass,true);
        var DirectionClass = Quill.import('attributors/class/direction');
        Quill.register(DirectionClass,true);
        var FontClass = Quill.import('attributors/class/font');
        Quill.register(FontClass,true);
        var SizeClass = Quill.import('attributors/class/size');
        Quill.register(SizeClass,true);
        var AlignStyle = Quill.import('attributors/style/align');
        Quill.register(AlignStyle,true);
        var BackgroundStyle = Quill.import('attributors/style/background');
        Quill.register(BackgroundStyle,true);
        var ColorStyle = Quill.import('attributors/style/color');
        Quill.register(ColorStyle,true);
        var DirectionStyle = Quill.import('attributors/style/direction');
        Quill.register(DirectionStyle,true);
        var FontStyle = Quill.import('attributors/style/font');
        Quill.register(FontStyle,true);
        var SizeStyle = Quill.import('attributors/style/size');
        Quill.register(SizeStyle,true);
        var LinkFormats = Quill.import("formats/link");
        Quill.register(LinkFormats,true);
        window.lang_editors = [];
        window.bulk_translate = false;
        $(document).ready(function () {
            var toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['clean']
            ];
            window.lang_editors['svt_products_lang'] = [];
            window.lang_editors['svt_products_lang']['description'] = [];
            $('div[data-table="svt_products_lang"][data-field="description"]').each(function() {
                var elem = $(this);
                var lang = elem.attr('data-lang');
                if(lang===undefined) lang = "orig";
                var id = elem.attr('data-id');
                window.lang_editors['svt_products_lang']['description'][lang+'-'+id] = new Quill(elem[0], {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    readOnly: (lang!=='orig') ? false : true,
                    theme: 'snow',
                });
                if(lang!=='orig') {
                    window.lang_editors['svt_products_lang']['description'][lang+'-'+id].on('text-change', function (delta, oldDelta, source) {
                        elem.trigger('input');
                    });
                }
            });
            window.lang_editors['svt_markers_lang'] = [];
            window.lang_editors['svt_markers_lang']['tooltip_text'] = [];
            $('div[data-table="svt_markers_lang"][data-field="tooltip_text"]').each(function() {
                var elem = $(this);
                var lang = elem.attr('data-lang');
                if(lang===undefined) lang = "orig";
                var id = elem.attr('data-id');
                window.lang_editors['svt_markers_lang']['tooltip_text'][lang+'-'+id] = new Quill(elem[0], {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    readOnly: (lang!=='orig') ? false : true,
                    theme: 'snow',
                });
                if(lang!=='orig') {
                    window.lang_editors['svt_markers_lang']['tooltip_text'][lang+'-'+id].on('text-change', function (delta, oldDelta, source) {
                        elem.trigger('input');
                    });
                }
            });
            window.lang_editors['svt_pois_lang'] = [];
            window.lang_editors['svt_pois_lang']['tooltip_text'] = [];
            $('div[data-table="svt_pois_lang"][data-field="tooltip_text"]').each(function() {
                var elem = $(this);
                var lang = elem.attr('data-lang');
                if(lang===undefined) lang = "orig";
                var id = elem.attr('data-id');
                window.lang_editors['svt_pois_lang']['tooltip_text'][lang+'-'+id] = new Quill(elem[0], {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    readOnly: (lang!=='orig') ? false : true,
                    theme: 'snow',
                });
                if(lang!=='orig') {
                    window.lang_editors['svt_pois_lang']['tooltip_text'][lang+'-'+id].on('text-change', function (delta, oldDelta, source) {
                        elem.trigger('input');
                    });
                }
            });
            window.lang_editors['svt_pois_lang']['content'] = [];
            $('div[data-table="svt_pois_lang"][data-field="content"]').each(function() {
                var elem = $(this);
                var lang = elem.attr('data-lang');
                if(lang===undefined) lang = "orig";
                var id = elem.attr('data-id');
                window.lang_editors['svt_pois_lang']['content'][lang+'-'+id] = new Quill(elem[0], {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    readOnly: (lang!=='orig') ? false : true,
                    theme: 'snow',
                });
                if(lang!=='orig') {
                    window.lang_editors['svt_pois_lang']['content'][lang+'-'+id].on('text-change', function (delta, oldDelta, source) {
                        elem.trigger('input');
                    });
                }
            });
            window.lang_editors['svt_pois_lang']['embed_content'] = [];
            $('div[data-table="svt_pois_lang"][data-field="embed_content"]').each(function() {
                var elem = $(this);
                var lang = elem.attr('data-lang');
                if(lang===undefined) lang = "orig";
                var id = elem.attr('data-id');
                window.lang_editors['svt_pois_lang']['embed_content'][lang+'-'+id] = new Quill(elem[0], {
                    modules: {
                        toolbar: toolbarOptions
                    },
                    readOnly: (lang!=='orig') ? false : true,
                    theme: 'snow',
                });
                if(lang!=='orig') {
                    window.lang_editors['svt_pois_lang']['embed_content'][lang+'-'+id].on('text-change', function (delta, oldDelta, source) {
                        elem.trigger('input');
                    });
                }
            });
            check_count_translated();
        });
        var elems_to_translate = [];
        $('.btn_bulk_translate').on('click',function() {
            var button = $(this);
            var html_btn = button.html();
            button.css('pointer-events','none');
            button.html(window.backend_labels.translating+'&nbsp;&nbsp;<i class="fas fa-spin fa-circle-notch"></i>')
            var parent = $(this).parent().parent();
            var table = parent.attr('data-table');
            $('.field_translate[data-table="'+table+'"]').each(function () {
                var id = $(this).attr('data-id');
                var type = $(this).attr('data-type');
                var field = $(this).attr('data-field');
                var lang = $(this).attr('data-lang');
                val_original = '';
                if(type=='editor') {
                    var elem = window.lang_editors[table][field][lang+'-'+id];
                    var val = window.lang_editors[table][field][lang+'-'+id].root.innerHTML;
                    if(val=='' || val=='<p><br></p>') {
                        var val_original = window.lang_editors[table][field]['orig-'+id].root.innerHTML;;
                    }
                } else {
                    var elem = $(this);
                    var val = elem.val();
                    if(val=='') {
                        switch(field) {
                            case 'form_content':
                            case 'form_poi_content':
                                var index = $(this).attr('data-index');
                                var form_field = $(this).attr('data-form_field');
                                var val_original = $('.translate_original_field[readonly][data-table="'+table+'"][data-field="'+field+'"][data-id="'+id+'"][data-index="'+index+'"][data-form_field="'+form_field+'"]').val();
                                break;
                            case 'callout_params':
                                var callout_field = $(this).attr('data-callout_field');
                                var val_original = $('.translate_original_field[readonly][data-table="'+table+'"][data-field="'+field+'"][data-id="'+id+'"][data-callout_field="'+callout_field+'"]').val();
                                break;
                            default:
                                var val_original = $('.translate_original_field[readonly][data-table="'+table+'"][data-field="'+field+'"][data-id="'+id+'"]').val();
                                break;
                        }
                    }
                }
                if(val_original!='') {
                    elems_to_translate.push([elem,val_original,lang,type]);
                }
            });
            window.bulk_translate = true;
            translate(elems_to_translate, function() {
                window.bulk_translate = false;
                button.html(html_btn);
                button.css('pointer-events','initial');
            });
        });
        $('.checkbox_only_translated').on('change',function () {
            if($(this).is(':checked')) {
                $('.checkbox_only_translated').prop('checked',true);
                $('.row_translate').each(function() {
                    var not_translated = 0;
                    $(this).find('.status_translate_icon').each(function() {
                        if($(this).css('color')!=='rgb(0, 128, 0)') {
                            not_translated++;
                        }
                    });
                    if(not_translated==0) {
                        $(this).hide();
                    }
                })
            } else {
                $('.checkbox_only_translated').prop('checked',false);
                $('.row_translate').show();
            }
        });
        $('.field_translate').on('input',function() {
            if(!window.bulk_translate) {
                clearTimeout(timeout_input);
            }
            var elem = $(this);
            elem.parent().find('.check_translate i').removeClass('fa-circle').addClass('fa-circle-notch').addClass('fa-spin');
            var id = $(this).attr('data-id');
            var type = $(this).attr('data-type');
            var param = $(this).attr('data-param');
            var field = $(this).attr('data-field');
            var table = $(this).attr('data-table');
            var lang = $(this).attr('data-lang');
            if(type=='editor') {
                var value = window.lang_editors[table][field][lang+'-'+id].root.innerHTML;
                if(value=='<p><br></p>') value='';
            } else {
                var value = $(this).val();
            }
            switch(field) {
                case 'form_content':
                    value = "";
                    var form_json = $('.field_translate[data-table="'+table+'"][data-field="form_content"][data-lang="'+lang+'"]').first().attr('data-param');
                    var form_content = JSON.parse(form_json);
                    $('.field_translate[data-table="'+table+'"][data-field="form_content"][data-lang="'+lang+'"]').each(function() {
                        var elem_s = $(this);
                        var value = elem_s.val();
                        var index = elem_s.attr('data-index');
                        var form_field = elem_s.attr('data-form_field');
                        form_content[index][form_field]=value;
                        if(value!='') {
                            elem_s.parent().find('.check_translate i').css('color','green');
                        } else {
                            elem_s.parent().find('.check_translate i').css('color','orange');
                        }
                    });
                    value = JSON.stringify(form_content);
                    break;
                case 'form_poi_content':
                    value = "";
                    var form_json = $('.field_translate[data-table="'+table+'"][data-field="form_poi_content"][data-lang="'+lang+'"]').first().attr('data-param');
                    var form_content = JSON.parse(form_json);
                    $('.field_translate[data-table="'+table+'"][data-field="form_poi_content"][data-lang="'+lang+'"]').each(function() {
                        var elem_s = $(this);
                        var value = elem_s.val();
                        var index = elem_s.attr('data-index');
                        var form_field = elem_s.attr('data-form_field');
                        form_content[index][form_field]=value;
                        if(value!='') {
                            elem_s.parent().find('.check_translate i').css('color','green');
                        } else {
                            elem_s.parent().find('.check_translate i').css('color','orange');
                        }
                    });
                    value = JSON.stringify(form_content);
                    field = 'content';
                    break;
                case 'callout_params':
                    value = "";
                    var callout_json = $('.field_translate[data-table="'+table+'"][data-id="'+id+'"][data-field="callout_params"][data-lang="'+lang+'"]').first().attr('data-param');
                    var callout_params = JSON.parse(callout_json);
                    $('.field_translate[data-table="'+table+'"][data-id="'+id+'"][data-field="callout_params"][data-lang="'+lang+'"]').each(function() {
                        var elem_s = $(this);
                        var value = elem_s.val();
                        var callout_field = elem_s.attr('data-callout_field');
                        callout_params[callout_field]=value;
                        if(value!='') {
                            elem_s.parent().find('.check_translate i').css('color','green');
                        } else {
                            elem_s.parent().find('.check_translate i').css('color','orange');
                        }
                    });
                    value = JSON.stringify(callout_params);
                    field = 'params';
                    break;
                default:
                    if(value!='') {
                        elem.parent().find('.check_translate i').css('color','green');
                    } else {
                        elem.parent().find('.check_translate i').css('color','orange');
                    }
                    break;
            }
            if(table=='svt_pois_lang' && type=='editor' && field=='embed_content') {
                if(value!='') {
                    value = value + ' border-width:'+param;
                }
            }
            timeout_input = setTimeout(function() {
                check_count_translated();
                $.ajax({
                    url: "ajax/save_bulk_translate.php",
                    type: "POST",
                    data: {
                        id: id,
                        field: field,
                        value: value,
                        table: table,
                        lang: lang
                    },
                    async: true,
                    success: function (json) {
                        var rsp = JSON.parse(json);
                        elem.parent().find('.check_translate i').addClass('fa-circle').removeClass('fa-circle-notch').removeClass('fa-spin');
                        if(rsp.status!='ok') {
                            elem.parent().find('.check_translate i').css('color','red');
                        }
                    },
                    error: function() {
                        elem.parent().find('.check_translate i').addClass('fa-circle').removeClass('fa-circle-notch').removeClass('fa-spin');
                        elem.parent().find('.check_translate i').css('color','red');
                    }
                });
            },(window.bulk_translate) ? 0 : 500);
        });
    })(jQuery); // End of use strict

    window.check_count_translated = function() {
        $('.block_translate').each(function () {
            var count_translated = 0;
            var count_total_translate = 0;
            var table = $(this).attr('data-table');
            $('.field_translate[data-table="'+table+'"]').each(function () {
                count_total_translate++;
                var type = $(this).attr('data-type');
                if(type=='editor') {
                    var id = $(this).attr('data-id');
                    var lang = $(this).attr('data-lang');
                    var field = $(this).attr('data-field');
                    var val = window.lang_editors[table][field][lang+'-'+id].root.innerHTML;
                    if(val!='' && val!='<p><br></p>') {
                        count_translated++;
                    }
                } else {
                    var val = $(this).val();
                    if(val!='') {
                        count_translated++;
                    }
                }
            });
            if(count_total_translate==0) {
                $(this).hide();
                var tab_id = $(this).parent().parent().parent().attr('id');
                $('.nav-link[href="#'+tab_id+'"]').addClass('disabled');
            } else {
                if(count_total_translate==count_translated) {
                    var color = 'green';
                    $(this).find('.btn_bulk_translate').addClass('disabled');
                } else {
                    var color = 'orange';
                    $(this).find('.btn_bulk_translate').removeClass('disabled');
                }
                $(this).find('.count_translated').html(count_translated);
                $(this).find('.count_total_translate').html(count_total_translate);
                $(this).find('.icon_check_translate').css('color',color);
            }
        });
    }

    window.change_translate_view = function(type) {
        switch(type) {
            case 'list':
                $('.grid_list_switcher .fa-table-list').addClass('active').addClass('text-primary');
                $('.grid_list_switcher .fa-table-cells').removeClass('active').removeClass('text-primary');
                $('.column_field_translate').removeClass('col-md-4').addClass('col-md-12');
                break;
            case 'grid':
                $('.grid_list_switcher .fa-table-list').removeClass('active').removeClass('text-primary');
                $('.grid_list_switcher .fa-table-cells').addClass('active').addClass('text-primary');
                $('.column_field_translate').removeClass('col-md-12').addClass('col-md-4');
                break;
        }
    }

    function translate(elems_to_translate, callback) {
        if (elems_to_translate.length === 0) {
            callback();
            return;
        }
        var elem_to_translate = elems_to_translate.shift();
        $.ajax({
            url: 'ajax/translate_deepl.php',
            async: true,
            type: 'POST',
            data: {
                language: elem_to_translate[2],
                text: elem_to_translate[1]
            },
            success: function(rsp) {
                var type = elem_to_translate[3];
                if(type=='editor') {
                    elem_to_translate[0].root.innerHTML=rsp;
                    elem_to_translate[0].update();
                } else {
                    elem_to_translate[0].val(rsp);
                    elem_to_translate[0].trigger('input');
                }
                setTimeout(function() {
                    translate(elems_to_translate, callback);
                },500);
            },
            error: function(error) {
                setTimeout(function() {
                    translate(elems_to_translate, callback);
                },250);
            }
        });
    }
</script>

<?php
function print_value_input($field,$array,$array_lang,$table,$input_type) {
    global $default_language,$array_languages;
    switch($field) {
        case 'media_title':
            $ui = json_decode($array['ui_style'],true);
            $array['media_title']=$ui['controls']['media']['label'];
            if($array['show_media']==0) return;
            break;
        case 'location_title':
            $ui = json_decode($array['ui_style'],true);
            $array['location_title']=$ui['controls']['location']['label'];
            if($array['show_location']==0) return;
            break;
        case 'custom_title':
            $ui = json_decode($array['ui_style'],true);
            $array['custom_title']=$ui['controls']['custom']['label'];
            if($array['show_custom']==0) return;
            break;
        case 'custom2_title':
            $ui = json_decode($array['ui_style'],true);
            $array['custom2_title']=$ui['controls']['custom2']['label'];
            if($array['show_custom2']==0) return;
            break;
        case 'custom3_title':
            $ui = json_decode($array['ui_style'],true);
            $array['custom3_title']=$ui['controls']['custom3']['label'];
            if($array['show_custom3']==0) return;
            break;
        case 'custom4_title':
            $ui = json_decode($array['ui_style'],true);
            $array['custom4_title']=$ui['controls']['custom4']['label'];
            if($array['show_custom4']==0) return;
            break;
        case 'custom5_title':
            $ui = json_decode($array['ui_style'],true);
            $array['custom5_title']=$ui['controls']['custom5']['label'];
            if($array['show_custom5']==0) return;
            break;
        default:
            if(empty($array[$field]) || $array[$field]=='<p><br></p>') {
                return '';
            }
            break;
    }
    $html = '<div class="row row_translate">';
    $disabled = "";
    if($table=='svt_pois_lang' && $array['embed_type']=='text') {
        $tmp=explode('border-width:',$array['embed_content']);
        $array['embed_content']=$tmp[0];
    }
    switch($input_type) {
        case 'text':
            $input = '<input readonly data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'" type="text" class="form-control translate_original_field" value="'.htmlspecialchars($array[$field]).'">';
            break;
        case 'textarea':
            $input = '<textarea readonly data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'" class="form-control translate_original_field">'.htmlspecialchars($array[$field]).'</textarea>';
            break;
        case 'editor':
            $input = '<div readonly data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'">'.($array[$field]).'</div>';
            $disabled = "disabled_editor";
            break;
    }
    $html .= '<div class="col-md-4 column_field_translate">
        <div class="input-group input-group-sm '.$disabled.'">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$default_language.'.png?v=2" /></span>
            </div>
            '.$input.'
        </div>
    </div>';
    foreach ($array_languages as $language) {
        if ($language != $default_language) {
            if($table=='svt_pois_lang' && $array['embed_type']=='text') {
                $tmp=explode('border-width:',$array_lang[$language]['embed_content']);
                $array_lang[$language]['embed_content']=$tmp[0];
                $border = $tmp[1];
            }
            switch($input_type) {
                case 'text':
                    $input = '<input data-type="text" data-lang="'.$language.'" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'" type="text" class="form-control field_translate" value="'.htmlspecialchars($array_lang[$language][$field]).'">';
                    break;
                case 'textarea':
                    $input = '<textarea data-type="textarea" data-lang="'.$language.'" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'" class="form-control field_translate">'.htmlspecialchars($array_lang[$language][$field]).'</textarea>';
                    break;
                case 'editor':
                    $input = '<div data-type="editor" data-lang="'.$language.'" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.$field.'" data-param="'.$border.'" class="field_translate">'.($array_lang[$language][$field]).'</div>';
                    break;
            }
            if($input_type == 'editor') {
                if($array_lang[$language][$field]=='<p><br></p>') $array_lang[$language][$field]='';
                $html .= '<div class="col-md-4 column_field_translate">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$language.'.png?v=2" /></span>
                        </div>
                        <div style="position:absolute;top:0;right:0;" class="input-group-prepend">
                            <span class="input-group-text bg-white check_translate"><i style="color:'.((empty($array_lang[$language][$field])) ? 'orange' : 'green').'" class="status_translate_icon fas fa-circle"></i></span>
                        </div>
                        '.$input.'
                    </div>
                </div>';
            } else {
                $html .= '<div class="col-md-4 column_field_translate">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$language.'.png?v=2" /></span>
                        </div>
                        '.$input.'
                        <div class="input-group-append">
                            <span class="input-group-text bg-white check_translate"><i style="color:'.((empty($array_lang[$language][$field])) ? 'orange' : 'green').'" class="status_translate_icon fas fa-circle"></i></span>
                        </div>
                    </div>
                </div>';
            }
        }
    }
    $html .= '<hr class="mt-0 mb-3"></div>';
    return $html;
}

function print_value_input_form($field,$index,$form_field,$array,$array_lang,$table) {
    global $default_language,$array_languages;
    if(empty($array[$field][$index][$form_field])) {
        return '';
    }
    $html = '<div class="row row_translate">';
    $html .= '<div class="col-md-4 column_field_translate">
        <div class="input-group input-group-sm">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$default_language.'.png?v=2" /></span>
            </div>
            <input readonly type="text" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.(($field=='content') ? 'form_poi_content' : $field).'" data-index="'.$index.'" data-form_field="'.$form_field.'" class="form-control translate_original_field" value="'.htmlspecialchars($array[$field][$index][$form_field]).'">
        </div>
    </div>';
    $form_content = $array[$field];
    foreach ($array_languages as $language) {
        if ($language != $default_language) {
            if(!isset($array_lang[$language][$field])) {
                $form_content = $array_lang[$language][$field];
            }
            $html .= '<div class="col-md-4 column_field_translate">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$language.'.png?v=2" /></span>
                        </div>
                        <input data-type="text" data-lang="'.$language.'" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="'.(($field=='content') ? 'form_poi_content' : $field).'" data-index="'.$index.'" data-form_field="'.$form_field.'" data-param=\''.htmlspecialchars(json_encode($form_content)).'\' type="text" class="form-control field_translate" value="'.htmlspecialchars($array_lang[$language][$field][$index][$form_field]).'">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white check_translate"><i style="color:'.((empty($array_lang[$language][$field][$index][$form_field])) ? 'orange' : 'green').'" class="status_translate_icon fas fa-circle"></i></span>
                        </div>
                    </div>
                </div>';
        }
    }
    $html .= '<hr class="mt-0 mb-3"></div>';
    return $html;
}

function print_value_input_callout($field,$callout_field,$array,$array_lang,$table) {
    global $default_language,$array_languages;
    if(empty($array[$field][$callout_field])) {
        return '';
    }
    $html = '<div class="row row_translate">';
    $html .= '<div class="col-md-4 column_field_translate">
        <div class="input-group input-group-sm">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$default_language.'.png?v=2" /></span>
            </div>
            <input readonly type="text" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="callout_params" data-callout_field="'.$callout_field.'" class="form-control translate_original_field" value="'.htmlspecialchars($array[$field][$callout_field]).'">
        </div>
    </div>';
    $callout_content = $array[$field];
    foreach ($array_languages as $language) {
        if ($language != $default_language) {
            if(!empty($array_lang[$language][$field])) {
                if($array_lang[$language][$field][$callout_field]==$array[$field][$callout_field]) {
                    $array_lang[$language][$field][$callout_field]="";
                }
                $callout_content = $array_lang[$language][$field];
            }
            $html .= '<div class="col-md-4 column_field_translate">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><img style="height:14px;width:22px;" src="img/flags_lang/'.$language.'.png?v=2" /></span>
                        </div>
                        <input data-type="text" data-lang="'.$language.'" data-id="'.$array['id'].'" data-table="'.$table.'" data-field="callout_params" data-callout_field="'.$callout_field.'" data-param=\''.htmlspecialchars(json_encode($callout_content)).'\' type="text" class="form-control field_translate" value="'.htmlspecialchars($array_lang[$language][$field][$callout_field]).'">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white check_translate"><i style="color:'.((empty($array_lang[$language][$field][$callout_field])) ? 'orange' : 'green').'" class="status_translate_icon fas fa-circle"></i></span>
                        </div>
                    </div>
                </div>';
        }
    }
    $html .= '<hr class="mt-0 mb-3"></div>';
    return $html;
}
?>