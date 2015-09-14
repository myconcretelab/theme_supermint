<?php
namespace Concrete\Package\ThemeSupermint;

use PageTemplate;
use PageTheme;
use Asset;
use AssetList;
use Package;
use Page;
use BlockType;
use SinglePage;
use Loader;
use Route;
use Events;
use URL;
use Core;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Concrete\Package\ThemeSupermint\Src\Models\ThemeSupermintOptions;
use Concrete\Package\ThemeSupermint\Src\Helper\MclInstaller;
use Concrete\Package\ThemeSupermint\Controller\Tools\PresetColors;
use Concrete\Core\Editor\Plugin;
use PageType;
use FileImporter;
use Concrete\Core\Backup\ContentImporter;
use FileList;
use PageList;
use StackList;
use Concrete\Core\StyleCustomizer\Style\ValueList;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package  {

	protected $pkgHandle = 'theme_supermint';
    protected $themeHandle = 'supermint';
		protected $appVersionRequired = '5.7.4';
		protected $pkgVersion = '3.0.4';
		protected $pkg;
    protected $pkgAllowsFullContentSwap = true;
    protected $startingPoint;

	public function getPackageDescription() {
		return t("Supermint responsive suit any kind of website.");
	}

	public function getPackageName() {
		return t("Supermint Theme");
	}

	public function install($data = array()) {

        $this->startingPoint = $data['spHandle'];

        if ($data['pkgDoFullContentSwap'] === '1' && $this->startingPoint === '0')
            throw new \Exception(t('You must choose a Starting point to Swap all content'));

		$pkg = parent::install();

	// Theme options
		$o = new \Concrete\Package\ThemeSupermint\Src\Models\ThemeSupermintOptions($c);
		$o->install_db($this->startingPoint);
    // Elements installing
    $this->installOrUpgrade($pkg);

	}

	private function installOrUpgrade($pkg) {

		$ci = new MclInstaller($pkg);
		$ci->importContentFile($this->getPackagePath() . '/config/install/base/single_page.xml');
		$ci->importContentFile($this->getPackagePath() . '/config/install/base/themes.xml');
		$ci->importContentFile($this->getPackagePath() . '/config/install/base/page_templates.xml');
		$ci->importContentFile($this->getPackagePath() . '/config/install/base/attributes.xml');
    $ci->importContentFile($this->getPackagePath() . '/config/install/base/blocktypes.xml');
		if(version_compare(APP_VERSION, '5.7.4.2') === 1):
			// We are 5.7.5+
			$ci->importContentFile($this->getPackagePath() . '/config/install/base/systemcontenteditorsnippets.xml');
		endif;
	}

	public function uninstall() {
	      parent::uninstall();
	      $db = Loader::db();
	      $db->execute("DROP TABLE SupermintOptions, SupermintOptionsPreset");
	}

	public function upgrade() {
        // Theme options
        $o = new \Concrete\Package\ThemeSupermint\Src\Models\ThemeSupermintOptions($c);
        $o->update_db();
        // All things
				$this->installOrUpgrade($this);
				parent::upgrade();
	}

    public function on_start() {
        $this->registerRoutes();
        $this->registerAssets();
        $this->registerEvents();
    }

    function registerEvents () {
        Events::addListener(
            'on_before_render',
            function($e) {
                $session = \Core::make('session');
								$c = Page::getCurrentPage();
                // Register options into the session
				        $options = ThemeSupermintOptions::get_options_from_active_preset_ID();
								$session->set('supermint.options',$options);

                // Register colors from active or default preset in the session
                if (is_object($c)) :
                    $colors = PresetColors::GetColorsFromPage();
                    $session->set('supermint.colors',$colors);
                endif;

								if (!is_object($c)) return;
								// Now we build the button
								$pt = $c->getCollectionThemeObject();
								if ($pt->getThemeHandle() != $this->themeHandle) return;
								$status = t('Supermint Options');
								$icon = 'toggle-on';
								$ihm = Core::make('helper/concrete/ui/menu');

								$ihm->addPageHeaderMenuItem('theme_supermint', 'theme_supermint',
								    array(
								        'label' => $status,
								        'icon' => $icon,
								        'position' => 'right',
								        'href' => URL::to('/dashboard/supermint_options/theme_options')
								    ));
            });
    }

    public function registerAssets () {
 		$al = AssetList::getInstance();
 		$al->register( 'javascript', 'boxnav', 'themes/supermint/js/jquery.boxnav.js', array('version' => '1.0'), $this );
 		$al->register( 'javascript', 'slick', 'themes/supermint/js/slick.min.js', array('version' => '1.5.0'), $this );
 		$al->register( 'javascript', 'fitvids', 'themes/supermint/js/jquery.fitvids.js', array('version' => '1.0'), $this );
 		$al->register( 'javascript', 'rcrumbs', 'themes/supermint/js/jquery.rcrumbs.min.js', array('version' => '1.1'), $this );
 		$al->register( 'javascript', 'nprogress', 'themes/supermint/js/nprogress.js', array('version' => '0.1.6'), $this );
 		$al->register( 'javascript', 'autohidingnavbar', 'themes/supermint/js/jquery.autohidingnavbar.js', array('version' => '0.1.6'), $this );
 		$al->register( 'javascript', 'supermint.script', 'themes/supermint/js/script.js', array('version' => '0.1.6'), $this );
   	$al->register( 'javascript', 'YTPlayer', 'themes/supermint/js/jquery.mb.YTPlayer.min.js', array('version' => '2.7.5'), $this );
		$al->register( 'javascript', 'modernizr.custom', 'themes/supermint/js/modernizr.custom.js', array('version' => '2.7.1'), $this );
		$al->register( 'javascript', 'transit', 'themes/supermint/js/jquery.transit.js', array('version' => '0.1'), $this );
    $al->register( 'javascript', 'isotope', 'themes/supermint/js/isotope.pkgd.min.js', array('version' => '2.1.1'), $this );
    $al->register( 'javascript', 'wow', 'themes/supermint/js/wow.js', array('version' => '1.1.2'), $this );
    $al->register( 'javascript', 'harmonize-text', 'themes/supermint/js/harmonize-text.js', array('version' => '1'), $this );
		$al->register( 'javascript', 'enquire', 'themes/supermint/js/enquire.js', array('version' => '2.1.2'), $this );
		$al->register( 'javascript', 'twitterFetcher', 'themes/supermint/js/twitterFetcher_min.js', array('version' => '12'), $this );

 		$al->register( 'css', 'YTPlayer', 'themes/supermint/css/addons/YTPlayer.css', array('version' => '2.7.5'), $this );
 		$al->register( 'css', 'slick', 'themes/supermint/css/addons/slick.css', array('version' => '1.5.0'), $this );
 		$al->register( 'css', 'slick-theme', 'themes/supermint/css/addons/slick-theme.css', array('version' => '1.5.0'), $this );
 		$al->register( 'css', 'buttons', 'themes/supermint/css/addons/buttons.css', array('version' => '1.3.4'), $this );
		$al->register( 'css', 'bootsrap-custom', 'themes/supermint/css/addons/bootstrap.custom.min.css', array('version' => '3.3.4'), $this );
		$al->register( 'css', 'animate', 'themes/supermint/css/addons/animate.css', array('version' => '1'), $this );
		$al->register( 'css', 'mega-menu', 'themes/supermint/css/addons/mega-menu.css', array('version' => '1.1.0'), $this );
		$al->register( 'css', 'transit', 'themes/supermint/css/addons/jquery.transit.css', array('version' => '0.1'), $this );

		// -- Redactor Plugins -- \\

        $pluginManager = Core::make('editor')->getPluginManager();
		// ThemeFont plugin
        $al->register('javascript', 'editor/plugin/themefontcolor', 'js/editor/themefontcolor.js', array(), $this);
        $al->register('css', 'editor/plugin/themefontcolor', 'css/editor/themefontcolor.css', array(), $this);
        $al->registerGroup('editor/plugin/themefontcolor', array(
            array('javascript', 'editor/plugin/themefontcolor'),
            array('css', 'editor/plugin/themefontcolor')
            ));

        $plugin = new Plugin();
        $plugin->setKey('themefontcolor');
        $plugin->setName('Font colors from theme');
        $plugin->requireAsset('editor/plugin/themefontcolor');

        $pluginManager->register($plugin);
		// themClips plugin
        $al->register('javascript', 'editor/plugin/themeclips', 'js/editor/themeclips.js', array(), $this);
        $al->register( 'javascript', 'chosen-icon', 'js/chosenIcon.jquery.js',  array(), 'theme_supermint' );
        $al->register( 'javascript', 'chosen.jquery.min', 'js/chosen.jquery.min.js',  array(), 'theme_supermint' );
        $al->register( 'css', 'chosenicon', 'css/chosenicon.css',  array(), 'theme_supermint' );
        $al->register( 'css', 'chosen.min', 'css/chosen.min.css', array(), 'theme_supermint' );

        $al->registerGroup('editor/plugin/themeclips', array(
            array('javascript', 'editor/plugin/themeclips'),
            array('javascript', 'chosen-icon'),
            array('javascript', 'chosen.jquery.min'),
            array('css', 'chosen.min'),
            array('css', 'chosenicon')
            ));

        $plugin = new Plugin();
        $plugin->setKey('themeclips');
        $plugin->setName('Snippets from Supermint');
        $plugin->requireAsset('editor/plugin/themeclips');

        $pluginManager->register($plugin);

	}


    public function registerRoutes() {
        Route::register(
            '/ThemeSupermint/tools/extend.js',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\ExtendJs::render'
        );
        Route::register(
            '/ThemeSupermint/tools/get_preset_colors',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\PresetColors::getColors'
        );
        Route::register(
            '/ThemeSupermint/tools/font_details',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\FontsTools::getFontDetails'
        );
        Route::register(
            '/ThemeSupermint/tools/font_url',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\FontsTools::getFontsURL'
        );
        Route::register(
            '/ThemeSupermint/tools/font_url_ajax',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\FontsTools::getFontURLAjax'
        );
        Route::register(
            '/ThemeSupermint/tools/override.css',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\OverrideCss::render'
        );
        Route::register(
            '/ThemeSupermint/tools/xml_preset',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\XmlPreset::render'
        );
        Route::register(
            '/ThemeSupermint/tools/get_awesome_icons',
            '\Concrete\Package\ThemeSupermint\Controller\Tools\AwesomeArray::getAwesomeArray'
        );
    }

    public function swapContent($options) {

        if ($this->validateClearSiteContents($options)) {
            \Core::make('cache/request')->disable();

            $pl = new PageList();
            $pages = $pl->getResults();
            foreach ($pages as $c) $c->delete();

            $fl = new FileList();
            $files = $fl->getResults();
            foreach ($files as $f) $f->delete();

            // clear stacks
            $sl = new StackList();
            foreach ($sl->get() as $c) $c->delete();

            $home = Page::getByID(HOME_CID);
            $blocks = $home->getBlocks();
            foreach ($blocks as $b) $b->deleteBlock();


            $pageTypes = PageType::getList();
            foreach ($pageTypes as $ct) $ct->delete();

						$startingPointFolder = $this->getPackagePath() . '/starting_points/'. $this->startingPoint;

            // Import Files
            if (is_dir($startingPointFolder . '/content_files')) {
                $ch = new ContentImporter();
                $computeThumbnails = true;
                if ($this->contentProvidesFileThumbnails()) {
                    $computeThumbnails = false;
                }
                $ch->importFiles($startingPointFolder . '/content_files', true );

            }

            // Install the starting point.
            if (is_file($startingPointFolder . '/content.xml')) :
                $ci = new ContentImporter();
                $ci->importContentFile($startingPointFolder . '/content.xml');
            endif;

            // Set it as default for the page theme
            $this->setPresetAsDefault($this->startingPoint);

            // Restore Cache
            \Core::make('cache/request')->enable();
        }
    }

    function setPresetAsDefault ($presetHandle) {
        $outputError = false;
        $baseExceptionText = t('The theme and the Starting point has been installed correctly but it\'s ');
        $pt = PageTheme::getByHandle($this->themeHandle);
        $preset = $pt->getThemeCustomizablePreset($presetHandle);
        if (!is_object($preset)) {
            if($outputError) throw new \Exception($baseExceptionText . t('impossible to retrieve the Preset selected : ' . $presetHandle));
            return;
        }
        $styleList = $pt->getThemeCustomizableStyleList();
        if (!is_object($styleList)) {
            if($outputError) throw new \Exception($baseExceptionText . t('impossible to retrieve the Style List from ' . $presetHandle));
            return;
        }
        $valueList = $preset->getStyleValueList();
        $vl = new ValueList();

        $sets = $styleList->getSets();
        if (!is_array($sets)) {
            if($outputError) throw new \Exception($baseExceptionText . t('impossible to retrieve the Style Set from ' . $presetHandle));
            return;
        }

        foreach ($sets as $set) :
         foreach($set->getStyles() as $style)  :
            $valueObject = $style->getValueFromList($valueList);
            if (is_object($valueObject))
                $vl->addValue($valueObject);
         endforeach;
        endforeach;

        $vl->save();
        $pt->setCustomStyleObject($vl, $preset);
    }

}
