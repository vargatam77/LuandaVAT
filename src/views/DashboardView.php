<?php
declare(strict_types=1);

namespace LuandaVAT\views;

use TamasVarga\LuandaPHP\Html;
use TamasVarga\LuandaPHP\Div;
use TamasVarga\LuandaPHP\Input;
use TamasVarga\LuandaPHP\form_input_type;
use TamasVarga\LuandaPHP\Text;
use TamasVarga\LuandaPHP\Span;
use TamasVarga\LuandaPHP\Label;
use TamasVarga\LuandaPHP\Misc\Svg;
use TamasVarga\LuandaPHP\Element;
use TamasVarga\LuandaPHP\input_events;
use TamasVarga\LuandaPHP\mouse_events;
use TamasVarga\LuandaPHP\Faicon;
use TamasVarga\LuandaPHP\Anchor;
use TamasVarga\LuandaPHP\Button;
use TamasVarga\LuandaPHP\form_button_type;
use LuandaVAT\controllers\AuthController;
use LuandaVAT\controllers\auth_req;

include __DIR__ . '/../controllers/AuthController.php';

class DashboardView {
    private string $theme;
    private AuthController $authController;
    
    private array $menuItems = [
    	'Dashboard'			=> './dashboard',
    	'File Return'		=> '#',
    	'History'			=> '#',
    	'Settings'			=> '#',
    	'Help & Support'	=> './contact',
    ];
    
    private array $navLinks = [
    	'HOME'		=> [auth_req::NONE, './dashboard'],
    	'LOGIN'		=> [auth_req::UNAUTH, './login'],
    	'CONTACT'	=> [auth_req::NONE, './contact'],
    	'LOGOUT'	=> [auth_req::BASIC | auth_req::ADMIN, './logout']
    ];

    public function __construct(string $theme = 'dark') {
        $this->theme = $theme;

        Element::Beautify();
    }

    // ── Theme switch (same as ContactView) ───────────────────

    private function createThemeSwitch(): array {
    	$_chk = new Input(form_input_type::CHKBOX);
    	$_chk->setId('theme-chk');
    	
    	if ($this->theme === 'light') $_chk->Check();
    	
    	$_chk->addEvent(
    		input_events::CHANGE,
    		"const t = this.checked ? 'light' : 'dark';
			document.querySelector('.page').dataset.theme = t;
			document.cookie = 'theme=' + t + ';path=/;max-age=31536000';"
    		);
    	
    	$_darkIcon = new Span();
    	$_darkIcon->addClass('theme-switch__icon theme-switch__icon--dark');
    	//$_darkIcon->addContent(new Text('DARK'));
    	$_darkIcon->addContent(new Faicon('moon'));
    	
    	$_track = new Span();
    	$_track->addClass('theme-switch__track');
    	
    	$_lightIcon = new Span();
    	$_lightIcon->addClass('theme-switch__icon theme-switch__icon--light');
    	//$_lightIcon->addContent(new Text('LIGHT'));
    	$_lightIcon->addContent(new Faicon('sun'));
    	
    	$_lbl = new Label();
    	$_lbl->setInput('theme-chk');
    	$_lbl->addClass('theme-switch');
    	$_lbl->addContent($_darkIcon);
    	$_lbl->addContent($_track);
    	$_lbl->addContent($_lightIcon);
    	
    	// Return both so createPage() can add them before the wrapper
    	return [$_chk, $_lbl];
    }

    // ── Logo ─────────────────────────────────────────────────

    private function createLogoDiv(): Div {
        $_logoDiv = new Div();
        $_logoDiv->addClass('logo');

        $_logoSvg = Svg::fromString($this->svgText);

        $_brandDiv = new Div();
        $_brandDiv->addClass('brand');
        $_brandDiv->addContent(new Text('Luanda VAT'));

        $_taglineDiv = new Div();
        $_taglineDiv->addClass('tagline');
        $_taglineDiv->addContent(new Text('welcome to your dashboard'));

        $_ornamentDiv = new Div();
        $_ornamentDiv->addClass('ornament');
        $_ornamentDiv->addContent(new Span());

        $_logoDiv->addContent($_logoSvg);
        $_logoDiv->addContent($_brandDiv);
        $_logoDiv->addContent($_taglineDiv);
        $_logoDiv->addContent($_ornamentDiv);

        return $_logoDiv;
    }

    // ── Header ────────────────────────────────────────────────

    private function createHeaderDiv(): Div {
        $_header = new Div();
        $_header->addClass('header');

        // Logo
        $_header->addContent($this->createLogoDiv());

        // Nav links — styled like .field underline animation
        $_nav = new Div();
        $_nav->addClass('header-nav');

        foreach ($this->navLinks as $_menu => $_linkData) {
        	if (($_linkData[0] & (auth_req::BASIC | auth_req::ADMIN)) == 0 || $_linkData[0] == (AuthController::isAuthenticated() + 1)) {
	            $_navLink = new Anchor($_linkData[1]);
	            $_navLink->addClass('nav-link');
	            $_navLink->addContent(new Text($_menu));
	
	            $_nav->addContent($_navLink);
        	}
        }

        $_header->addContent($_nav);

        return $_header;
    }

    // ── Left Aside (menu) ─────────────────────────────────────

    private function createLeftAside(): Div {
        $_aside = new Div();
        $_aside->addClass('aside aside--left');

        // Menu title
        $_menuTitle = new Div();
        $_menuTitle->addClass('menu-title');
        $_menuTitle->addContent(new Text('MENU'));
        $_aside->addContent($_menuTitle);

        // Menu items from array
        $_menuList = new Div();
        $_menuList->addClass('menu-list');

        foreach ($this->menuItems as $_menu => $_link) {
        	
        	$_btnText = new Span();
        	$_btnText->addContent(new Text(strtoupper($_menu)));
        	
        	$_btn = new Button(form_button_type::BTN);
        	$_btn->addClass('btn');
        	$_btn->addContent($_btnText);
        	$_btn->addEvent(mouse_events::CLICK, "window.location.href='" . $_link . "';");

            $_menuList->addContent($_btn);
        }

        $_aside->addContent($_menuList);

        return $_aside;
    }

    // ── Main content area ─────────────────────────────────────

    private function createMainArea(): Div {
        $_main = new Div();
        $_main->addClass('main');

        // Empty placeholder
        $_placeholder = new Div();
        $_placeholder->addClass('main-placeholder');

        $_phText = new Text('Content loads here...');
        $_placeholder->addContent($_phText);

        $_main->addContent($_placeholder);

        return $_main;
    }
    
    // ── Wrapper for main ───────────────────────────────────────────
    
    private function createMainWrapper(): Div {
    	$_main = new Div();
    	$_main->addClass('main-wrap');
    	
    	$_main->addContent($this->createMainArea());
    	
    	return $_main;
    }

    // ── Right Aside ───────────────────────────────────────────

    private function createRightAside(): Div {
        $_aside = new Div();
        $_aside->addClass('aside aside--right');

        $_title = new Div();
        $_title->addClass('aside-title');
        $_title->addContent(new Text('UPDATES'));
        $_aside->addContent($_title);

        $_ph = new Div();
        $_ph->addClass('aside-placeholder');
        $_ph->addContent(new Text('Ads & news will appear here...'));
        $_aside->addContent($_ph);

        return $_aside;
    }

    // ── Footer ────────────────────────────────────────────────

    private function createFooterDiv(): Div {
        $_footer = new Div();
        $_footer->addClass('footer');

        $_footer->addContent($_spnTM = new Span());
        $_spnTM->addContent(new Text('LuandaVAT™ — Tamas Varga 2026 — Powered by LuandaPHP™ 2.1.0'));
        
        $_footer->addContent($_spnFA = new Span());
        $_spnFA->addContent(new Text('Awesome icons by Font Awesome - https://fontawesome.com'));

        return $_footer;
    }

    // ── Hamburger (mobile menu toggle) ────────────────────────

    private function createHamburger(): Div {
        $_hamburger = new Div();
        $_hamburger->addClass('hamburger');
        $_hamburger->addContent(new Text('&'));

        $_hamburger->addEvent(
            mouse_events::CLICK,
            "document.querySelector('.aside--left').classList.toggle('aside--open');
            document.querySelector('.hamburger').classList.toggle('hamburger--active');"
        );

        return $_hamburger;
    }

    // ── Page wrapper ──────────────────────────────────────────

    private function createPageDiv(string $defaultTheme = 'dark'): Div {
        $_page = new Div();
        $_page->addClass('page');
        $_page->addAttr('data-theme', $this->theme);

        [$_switchChk, $_switchLbl] = $this->createThemeSwitch();
        $_page->addContent($_switchChk);
        $_page->addContent($_switchLbl);

        // Hamburger (mobile only — hidden on desktop via CSS)
        $_page->addContent($this->createHamburger());

        // Full layout wrapper
        $_layout = new Div();
        $_layout->addClass('layout');

        $_layout->addContent($this->createHeaderDiv());
        $_layout->addContent($this->createLeftAside());
        $_layout->addContent($this->createMainWrapper());
        $_layout->addContent($this->createRightAside());
        $_layout->addContent($this->createFooterDiv());

        $_page->addContent($_layout);

        return $_page;
    }

    // ── Page entry point ──────────────────────────────────────

    public function createPage(): Html {
    	$_page = new Html('LuandaVAT — Dashboard');
    	$_page->setBaseUrl('https://www.luandavat.co.uk/');

        $_page->addStylesheet('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Cinzel:wght@400;500&family=Bodoni+Moda:ital,wght@0,400;0,500;1,400&family=Raleway:wght@300;400;500&display=swap');
        $_page->addStylesheet('public/css/style.css?ver=' . time());
        $_page->addStylesheet('public/css/theme-switch.css?ver=' . time());
        $_page->addStylesheet('public/css/dashboard.css?ver=' . time());

        $_page->setupMobile();
        $_page->setupFontAwesome();

        $_page->addContent($this->createPageDiv('dark'));

        return $_page;
    }

    // ── SVG (shared with ContactView) ─────────────────────────

    private string $svgText =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" class="luanda-logo">
        <defs>
            <mask id="logoStencil">
                <path class="draw-path hex-m" d="M60 10 L105 35 V85 L60 110 L15 85 V35 Z"
                    fill="none" stroke="white" stroke-width="4"
                    stroke-linecap="round" stroke-linejoin="round"/>
                <path class="draw-path l-m" d="M45 40 V80 H75"
                    fill="none" stroke="white" stroke-width="8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </mask>
            <linearGradient id="shineGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%"   stop-color="rgba(255,255,255,0)"/>
                <stop offset="42%"  stop-color="rgba(255,255,255,0)"/>
                <stop offset="50%"  stop-color="#ffffff"/>
                <stop offset="54%"  stop-color="#ffe9b8"/>
                <stop offset="62%"  stop-color="rgba(255,255,255,0)"/>
                <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
            </linearGradient>
        </defs>
        <g class="logo-glow" opacity="0.4">
            <path d="M60 10 L105 35 V85 L60 110 L15 85 V35 Z"
                fill="none" stroke="#C5A059" stroke-width="5" stroke-linejoin="round"/>
            <path d="M45 40 V80 H75"
                fill="none" stroke="#C5A059" stroke-width="9"
                stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <g mask="url(#logoStencil)">
            <rect x="0" y="0" width="120" height="120" fill="#b89045" opacity="0.78"/>
        </g>
        <g mask="url(#logoStencil)">
            <rect class="shine-pass" x="-320" y="-220" width="420" height="420"
                fill="url(#shineGradient)" transform="rotate(0 60 60)"/>
        </g>
    </svg>';
}

?>