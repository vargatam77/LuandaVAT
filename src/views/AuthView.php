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
use TamasVarga\LuandaPHP\Form;
use TamasVarga\LuandaPHP\Misc\Svg;
use TamasVarga\LuandaPHP\Element;
use TamasVarga\LuandaPHP\Anchor;
use TamasVarga\LuandaPHP\Button;
use TamasVarga\LuandaPHP\form_button_type;
use TamasVarga\LuandaPHP\Faicon;
use TamasVarga\LuandaPHP\mouse_events;
use TamasVarga\LuandaPHP\Paragraph;
use TamasVarga\LuandaPHP\popover_state;
use TamasVarga\LuandaPHP\Dialog;
use TamasVarga\LuandaPHP\input_events;

class AuthView {
	private ?string $theme = null;
	
	public function __construct(string $theme = 'dark') {
		$this->theme = $theme;
		
		Element::Minify();
	}
	
	// ── Tab definitions ──────────────────────────────────────
	
	private array $tabProps = [
		'tab-login'    => 'SIGN<BR/>IN',
		'tab-register' => 'CREATE<BR/>ACCOUNT',
		'tab-logout'   => 'LEAVE<BR/>FOREVER',
	];
	
	// ── Theme switch ─────────────────────────────────────────
	//
	// The checkbox state is read by theme-switch.css to slide the thumb.
	// The JS below also flips data-theme on <html> so style.css tokens update.
	
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
		$_taglineDiv->addContent(new Text('private access'));
		
		$_ornamentDiv = new Div();
		$_ornamentDiv->addClass('ornament');
		$_ornamentDiv->addContent(new Span());
		
		$_logoDiv->addContent($_logoSvg);
		$_logoDiv->addContent($_brandDiv);
		$_logoDiv->addContent($_taglineDiv);
		$_logoDiv->addContent($_ornamentDiv);
		
		return $_logoDiv;
	}
	
	// ── Tabs ─────────────────────────────────────────────────
	
	private function createTabsDiv(): Div {
		$_tabsDiv = new Div();
		$_tabsDiv->addClass('tabs');
		
		foreach ($this->tabProps as $_id => $_text) {
			$_tabLbl = new Label();
			$_tabLbl->setInput($_id);
			$_tabLbl->addContent(new Text($_text));
			$_tabsDiv->addClone($_tabLbl);
		}
		
		return $_tabsDiv;
	}
	
	// ── Shared field builder ──────────────────────────────────
	
	private function buildField(
		string $type,
		string $id,
		string $name,
		string $labelText,
		int $minLen,
		int $maxLen,
		string $autocomplete,
		bool $withEye = false
		): Div {
			$_div = new Div();
			$_div->addClass('field');
			
			$_inp = new Input($type);
			$_inp->setPlaceholder(' ');
			$_inp->setId($id);
			$_inp->setName($name);
			$_inp->addAutocompletes($autocomplete);
			$_inp->toRequired();
			$_inp->setMinMaxLen($minLen, $maxLen);
			
			$_lbl = new Label();
			$_lbl->setInput($id);
			$_lbl->addContent(new Text($labelText));
			
			$_div->addContent($_inp);
			$_div->addContent($_lbl);
			
			if ($withEye) {
				$_eye = new Faicon(faicon_icons::PWD);
				$_eye->addClass('faicon');
				$_eye->addEvent(
					mouse_events::CLICK,
					"this.classList.toggle('fa-" . faicon_icons::TEXT . "');"
					. "this.classList.toggle('fa-" . faicon_icons::PWD . "');"
					. "{$id}.type=({$id}.type==='text'?'password':'text');{$id}.focus();"
					);
				
				$_div->addContent($_eye);
			}
			
			return $_div;
	}
	
	// ── Checkbox meta-row builder ─────────────────────────────
	
	private function buildCheckRow(
		string $checkId,
		string $labelText,
		string $onChangeJs,
		?Anchor $rightAnchor = null
		): Div {
			$_metaDiv = new Div();
			$_metaDiv->addClass('meta-row');
			
			$_wrapDiv = new Div();
			$_wrapDiv->addClass('check-wrap');
			
			$_chk = new Input(form_input_type::CHKBOX);
			$_chk->setId($checkId);
			$_chk->addEvent(input_events::CHANGE, $onChangeJs);
			
			$_lbl = new Label();
			$_lbl->setInput($checkId);
			$_lbl->addContent(new Text($labelText));
			
			$_wrapDiv->addContent($_chk);
			$_wrapDiv->addContent($_lbl);
			
			$_metaDiv->addContent($_wrapDiv);
			
			if ($rightAnchor !== null)
				$_metaDiv->addContent($rightAnchor);
				
				return $_metaDiv;
	}
	
	// ── Login form ────────────────────────────────────────────
	
	private function createLoginForm(): Form {
		$_form = new Form();
		$_form->setId('l_form');
		$_form->addClass('form-panel login-panel');
		
		$_forgotLink = new Anchor('#');
		$_forgotLink->addClass('forgot');
		$_forgotLink->addContent(new Text('Forgot password'));
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('Membership is by registration only.'));
		
		$_btnText = new Span();
		$_btnText->addContent(new Text('ENTER YOUR VAULT'));
		
		$_btn = new Button(form_button_type::BTN);
		$_btn->addClass('btn');
		$_btn->addContent($_btnText);
		
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'l_email', 'login_email',
			'EMAIL ADDRESS', 6, 60, 'username'
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'l_pass', 'login_password',
			'PASSWORD', 12, 60, 'current-password', withEye: true
			));
		$_form->addContent($this->buildCheckRow(
			'remember', 'REMEMBER ME',
			'if(this.checked)warning.showPopover();',
			$_forgotLink
			));
		$_form->addContent($_btn);
		$_form->addContent($_note);
		
		return $_form;
	}
	
	// ── Register form ─────────────────────────────────────────
	
	private function createRegisterForm(): Form {
		$_form = new Form();
		$_form->setId('r-form');
		$_form->addClass('form-panel register-panel');
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('Your details are held in strictest confidence.'));
		
		$_btn = new Button(form_button_type::BTN);
		$_btn->addClass('btn');
		$_btn->addContent(new Text('CLAIM YOUR VAULT'));
		
		$_form->addContent($this->buildField(
			form_input_type::TEXT, 'r_name', 'register_name',
			'FULL NAME', 2, 60, 'name'
			));
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'r_email', 'register_email',
			'EMAIL ADDRESS', 6, 60, 'username'
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'r_pass', 'register_password',
			'PASSWORD', 12, 60, 'new-password', withEye: true
			));
		$_form->addContent($_btn);
		$_form->addContent($_note);
		
		return $_form;
	}
	
	// ── Leave form ────────────────────────────────────────────
	
	private function createLeaveForm(): Form {
		$_form = new Form();
		$_form->setId('x-form');
		$_form->addClass('form-panel logout-panel');
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('This action is permanent and cannot be undone.'));
		
		$_btn = new Button(form_button_type::BTN);
		$_btn->addClass('btn btn-danger');
		$_btn->addContent(new Text('DEMOLISH YOUR VAULT'));
		
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'x_email', 'leave_email',
			'EMAIL ADDRESS', 6, 60, 'username'
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'x_pass', 'leave_password',
			'CONFIRM PASSWORD', 12, 60, 'current-password', withEye: true
			));
		$_form->addContent($this->buildCheckRow(
			'send_data', 'SEND MY DATA',
			'if(this.checked)data_warning.showPopover();'
			));
		$_form->addContent($_btn);
		$_form->addContent($_note);
		
		return $_form;
	}
	
	// ── Forms wrapper ─────────────────────────────────────────
	
	private function createFormDiv(): Div {
		$_div = new Div();
		$_div->addClass('forms');
		
		$_div->addContent($this->createLoginForm());
		$_div->addContent($this->createRegisterForm());
		$_div->addContent($this->createLeaveForm());
		
		return $_div;
	}
	
	// ── Popovers ─────────────────────────────────────────────
	
	private function createPopover(
		string $id,
		string $headText,
		string $bodyText
		): Dialog {
			$_dlg = new Dialog();
			$_dlg->setPopover(popover_state::AUTO);
			$_dlg->setId($id);
			$_dlg->addClass('remember-warning');
			
			$_head = new Span();
			$_head->addContent(new Text($headText));
			
			$_body = new Paragraph();
			$_body->addContent(new Text($bodyText));
			
			$_dlg->addContent($_head);
			$_dlg->addContent($_body);
			
			return $_dlg;
	}
	
	// ── Card ─────────────────────────────────────────────────
	
	private function createCardDiv(): Div {
		$_card = new Div();
		$_card->addClass('card');
		
		// Radio inputs for CSS tab control
		foreach (array_keys($this->tabProps) as $_id) {
			$_radio = new Input(form_input_type::RADIOBTN);
			$_radio->setName('tab');
			$_radio->setId($_id);
			if (str_contains($_id, 'login'))
				$_radio->Check();
				$_card->addClone($_radio);
		}
		
		$_card->addContent($this->createLogoDiv());
		$_card->addContent($this->createTabsDiv());
		$_card->addContent($this->createFormDiv());
		
		$_card->addContent($this->createPopover(
			'warning',
			'⚠ SECURITY NOTICE',
			'Staying signed in on shared devices may expose your account.'
			));
		$_card->addContent($this->createPopover(
			'data_warning',
			'⚠ DATA NOTICE',
			'Your saved data will be sent to your registered email before deletion. This may take a few minutes.'
			));
		
		$_powered = new Div();
		$_powered->addClass('powered');
		$_powered->addContent(new Text('Powered by LuandaPHP 2.1.0'));
		$_card->addContent($_powered);
		
		return $_card;
	}
	
	// ── Page fixed frame ──────────────────────────────────────
	// The .page div is position:fixed, inset:0, 100dvh — the Google
	// recommended pattern to kill iOS overscroll bounce on web apps.
	// It carries data-theme so all CSS token selectors cascade from it,
	// and the JS onchange on the switch checkbox updates it live.
	
	private function createPageDiv(): Div {
		$_page = new Div();
		$_page->addClass('page');
		$_page->addAttr('data-theme', $this->theme);
		
		// Theme switch sits inside .page so CSS sibling selector works:
		// #theme-chk:checked ~ .theme-switch (both children of .page)
		[$_switchChk, $_switchLbl] = $this->createThemeSwitch();
		$_page->addContent($_switchChk);
		$_page->addContent($_switchLbl);
		
		$_page->addContent($this->createWrapDiv());
		
		return $_page;
	}
	
	// ── Card wrapper ──────────────────────────────────────────
	
	private function createWrapDiv(): Div {
		$_wrap = new Div();
		$_wrap->addClass('wrapper');
		$_wrap->addContent($this->createCardDiv());
		return $_wrap;
	}
	
	// ── Page entry point ──────────────────────────────────────
	
	public function createPage(): Html {
		$_page = new Html('LuandaVAT - private access');
		
		$_page->addStylesheet('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Cinzel:wght@400;500&family=Bodoni+Moda:ital,wght@0,400;0,500;1,400&family=Raleway:wght@300;400;500&display=swap');
		$_page->addStylesheet('css/style.css?ver=' . time());
		$_page->addStylesheet('css/theme-switch.css?ver=' . time());
		
		$_page->setupMobile();
		$_page->setupFontAwesome();
		
		$_page->addContent($this->createPageDiv());
		
		return $_page;
	}
	
	// ── SVG source ────────────────────────────────────────────
	
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

class faicon_icons {
	public const TEXT = 'eye';
	public const PWD  = 'eye-slash';
}

?>