<?php
if(!isset($settings)) {
    $settings = get_settings();
}
$max_concurrent_sessions = (isset($settings['max_concurrent_sessions'])) ? $settings['max_concurrent_sessions'] : 0;
$active_sessions = checkActiveSessions($_SESSION['id_user'],$max_concurrent_sessions);
if ($active_sessions==2) {
    $warning_message = _('Warning: Another user with the same account is already logged in.');
} else {
    $warning_message = sprintf(_('Warning: %d other users with the same account are already logged in.'), ($active_sessions-1));
}
?>
<?php if($active_sessions>1) : ?>
    <div id="warning_concurrent_sessions" style="display:none" class="alert alert-warning alert-dismissible fade show shadow mb-4" role="alert">
        <?php echo $warning_message; ?>
        <button onclick="close_warning_concurrent_sessions();" type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <script>
        if (sessionStorage.getItem('warning_concurrent_sessions_dismissed')=='1') {
            document.getElementById('warning_concurrent_sessions').style.display = 'none';
        } else {
            document.getElementById('warning_concurrent_sessions').style.display = 'block';
        }
        function close_warning_concurrent_sessions() {
            sessionStorage.setItem('warning_concurrent_sessions_dismissed','1');
        }
    </script>
<?php endif; ?>