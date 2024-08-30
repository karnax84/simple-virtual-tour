<?php
if(!isset($settings)) {
    $settings = get_settings();
}
if(!isset($user_info)) {
    $user_info = get_user_info($_SESSION['id_user']);
}
$max_storage_space = $user_info['max_storage_space'];
$storage_space = $user_info['storage_space'];
if($max_storage_space>=1000) {
    $max_storage_space_f = ($max_storage_space/1000)." GB";
} else {
    $max_storage_space_f = $max_storage_space." MB";
}
$disabled_upload=false;
?>
<?php if($storage_space>=$max_storage_space && $max_storage_space!=-1) : ?>
    <div id="warning_quota" style="display:none" class="alert alert-warning alert-dismissible fade show shadow mb-4" role="alert">
        <?php $disabled_upload=true; echo sprintf(_('You have reached your quota limit of %s! Please update your plan or delete some contents.'),$max_storage_space_f); ?>
        <button onclick="close_warning_quota();" type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <script>
        if (sessionStorage.getItem('warning_quota_dismissed')=='1') {
            document.getElementById('warning_quota').style.display = 'none';
        } else {
            document.getElementById('warning_quota').style.display = 'block';
        }
        function close_warning_quota() {
            sessionStorage.setItem('warning_quota_dismissed','1');
        }
    </script>
<?php endif; ?>