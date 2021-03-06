
        <nav class="rex-nav-top navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".rex-nav-main > .navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <?php if (rex_be_controller::getCurrentPageObject()->isPopup()): ?>
                        <span class="navbar-brand"><img class="rex-js-svg rex-redaxo-logo" src="<?= rex_url::assets('redaxo-logo.svg') ?>" /></span>
                    <?php else: ?>
                        <a class="navbar-brand" href="<?= rex_url::backendController() ?>"><img class="rex-js-svg rex-redaxo-logo" src="<?= rex_url::assets('redaxo-logo.svg') ?>" /></a>
                    <?php endif; ?>
                </div>
                <?= $this->meta_navigation ?>
            </div>
        </nav>
