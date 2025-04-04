<div id="account_password" class="card teal accent-4">
    <div class="card-content">
        <form method="post" action="<?php echo base_url(true) . '/' . current_lang() ?>/account-settings/update-password" enctype="multipart/form-data">
            <div class="row">
                <div class="input-field col s12">
                    <label class="auth-form-label"><?php _t('common.current_password'); ?></label>
                    <input type="text" name="current_password" value="">
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <label class="auth-form-label"><?php _t('common.new_password'); ?></label>
                    <input type="password" name="new_password" value="">
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <label class="auth-form-label"><?php _t('common.confirm_password'); ?></label>
                    <input type="password" name="confirm_password" value="">
                </div>
            </div>
            <div class="row">
                <input type="hidden" name="csrf-token" value="<?php echo csrf_token() ?>"/>
                <input type="hidden" name="uuid" value="<?php echo auth()->user()->uuid ?>"/>
                <button class="btn btn-large waves-effect waves-light" type="submit">
                    <?php _t('common.save') ?>
                </button>
            </div>
        </form>
    </div>
</div>