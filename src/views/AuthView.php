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
use TamasVarga\LuandaPHP\image_mime_types;
use TamasVarga\LuandaPHP\form_method;
use TamasVarga\LuandaPHP\script_type;
use TamasVarga\LuandaPHP\Misc\IncidentReporter;

class AuthView {
	private ?string $theme     = null;
	private string  $activeTab = form_tabs::LOGIN;
	
	public PageContract     $pageContract;
	public LoginContract    $loginContract;
	public RegisterContract $registerContract;
	public DeleteContract   $deleteContract;
	
	public function __construct(string $theme = 'dark') {
		$this->theme = $theme;
		
		$this->pageContract     = new PageContract();
		$this->loginContract    = new LoginContract();
		$this->registerContract = new RegisterContract();
		$this->deleteContract   = new DeleteContract();
		
		Element::Beautify();
	}
	
	public function setActiveTab(string $tabId): void {
		$this->activeTab = $tabId;
	}
	
	// ── Tab definitions ──────────────────────────────────────
	
	private array $tabProps = [
		form_tabs::LOGIN	=> 'SIGN<BR/>IN',
		form_tabs::REGISTER	=> 'CREATE<BR/>ACCOUNT',
		form_tabs::DELETE	=> 'LEAVE<BR/>FOREVER',
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
	
	// ── CSRF hidden input ─────────────────────────────────────
	//
	// Inserted as the first element of every form so the token is always
	// submitted regardless of which fields the user fills.
	
	private function buildCsrfInput(): Input {
		$_inp = new Input(form_input_type::HIDDEN);
		$_inp->setName('csrf_token');
		$_inp->addAttr('value', $this->pageContract->csrfToken);
		
		return $_inp;
	}
	
	// ── Shared field builder ──────────────────────────────────
	//
	// $state carries pre-fill value and error message from the controller.
	// Password fields never receive a pre-fill value (security).
	// When an error is present the input gains class 'input-error' and an
	// absolutely-positioned <span class="field-error-msg"> is appended to
	// the .field wrapper so the surrounding layout is not disturbed.
	
	private function buildField(string $type, string $id, string $name, string $labelText, int $minLen, int $maxLen, string $autocomplete, bool $withEye = false, ?FieldState $state = null): Div {
		$_div = new Div();
		$_div->addClass('field');
		
		$_inp = new Input($type);
		$_inp->setPlaceholder(' ');
		$_inp->setId($id);
		$_inp->setName($name);
		$_inp->addAutocompletes($autocomplete);
		$_inp->toRequired();
		$_inp->setMinMaxLen($minLen, $maxLen);
		
		if ($state !== null && $state->value !== '')
			$_inp->value = $state->value;
			
		if ($state !== null && $state->error !== '')
			$_inp->addClass('input-error');
			
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
		
		if ($state !== null && $state->error !== '') {
			$_errSpan = new Span();
			$_errSpan->addClass('field-error-msg');
			$_errSpan->addContent(new Text($state->error));
			$_div->addContent($_errSpan);
		}
		
		return $_div;
	}
	
	// ── Checkbox meta-row builder ─────────────────────────────
	//
	// $checked restores checkbox state after a failed submission.
	// Checkboxes carry no error state — they cannot be invalid.
	
	private function buildCheckRow(string $checkId, string $labelText, string $onChangeJs, ?Anchor $rightAnchor = null, bool $checked = false): Div {
		$_metaDiv = new Div();
		$_metaDiv->addClass('meta-row');
		
		$_wrapDiv = new Div();
		$_wrapDiv->addClass('check-wrap');
		
		$_chk = new Input(form_input_type::CHKBOX);
		$_chk->setId($checkId);
		$_chk->addEvent(input_events::CHANGE, $onChangeJs);
		
		if ($checked) $_chk->Check();
		
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
		$_form->setMethod(form_method::POST);
		$_form->setAction('auth/login');
		$_form->addClass('form-panel login-panel');
		
		$_forgotLink = new Anchor('#');
		$_forgotLink->addClass('forgot');
		$_forgotLink->addContent(new Text('Forgot password'));
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('Membership is by registration only.'));
		
		$_btnText = new Span();
		$_btnText->addContent(new Text('ENTER YOUR VAULT'));
		
		$_btn = new Button(form_button_type::SUBMIT);
		$_btn->addClass('btn');
		$_btn->addContent($_btnText);
		$_btn->addEvent(mouse_events::CLICK, '');
		
		$_form->addContent($this->buildCsrfInput());
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'l_email', 'login_email',
			'EMAIL ADDRESS', 6, 60, 'username',
			state: $this->loginContract->email
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'l_pass', 'login_password',
			'PASSWORD', 12, 60, 'current-password', withEye: true,
			state: $this->loginContract->password
			));
		$_form->addContent($this->buildCheckRow(
			'remember', 'REMEMBER ME',
			'if(this.checked)warning.showPopover();',
			$_forgotLink,
			$this->loginContract->remember
			));
		$_form->addContent($_btn);
		$_form->addContent($_note);
		
		return $_form;
	}
	
	// ── Register form ─────────────────────────────────────────
	
	private function createRegisterForm(): Form {
		$_form = new Form();
		$_form->setId('r-form');
		$_form->setMethod(form_method::POST);
		$_form->setAction('auth/register');
		$_form->addClass('form-panel register-panel');
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('Your details are held in strictest confidence.'));
		
		$_btnText = new Span();
		$_btnText->addContent(new Text('CLAIM YOUR VAULT'));
		
		$_btn = new Button(form_button_type::SUBMIT);
		$_btn->addClass('btn btn-danger');
		$_btn->addContent($_btnText);
		
		$_form->addContent($this->buildCsrfInput());
		$_form->addContent($this->buildField(
			form_input_type::TEXT, 'r_name', 'register_name',
			'FULL NAME', 2, 60, 'name',
			state: $this->registerContract->name
			));
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'r_email', 'register_email',
			'EMAIL ADDRESS', 6, 60, 'username',
			state: $this->registerContract->email
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'r_pass', 'register_password',
			'PASSWORD', 12, 60, 'new-password', withEye: true,
			state: $this->registerContract->password
			));
		$_form->addContent($_btn);
		$_form->addContent($_note);
		
		return $_form;
	}
	
	// ── Leave form ────────────────────────────────────────────
	
	private function createLeaveForm(): Form {
		$_form = new Form();
		$_form->setId('x-form');
		$_form->setMethod(form_method::POST);
		$_form->setAction('auth/delete');
		$_form->addClass('form-panel delete-panel');
		
		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('This action is permanent and cannot be undone.'));
		
		$_btnText = new Span();
		$_btnText->addContent(new Text('DEMOLISH YOUR VAULT'));
		
		$_btn = new Button(form_button_type::SUBMIT);
		$_btn->addClass('btn btn-danger');
		$_btn->addContent($_btnText);
		
		$_form->addContent($this->buildCsrfInput());
		$_form->addContent($this->buildField(
			form_input_type::EMAIL, 'x_email', 'leave_email',
			'EMAIL ADDRESS', 6, 60, 'username',
			state: $this->deleteContract->email
			));
		$_form->addContent($this->buildField(
			form_input_type::PWD, 'x_pass', 'leave_password',
			'CONFIRM PASSWORD', 12, 60, 'current-password', withEye: true,
			state: $this->deleteContract->password
			));
		$_form->addContent($this->buildCheckRow(
			'send_data', 'SEND MY DATA',
			'if(this.checked)data_warning.showPopover();',
			null,
			$this->deleteContract->send_data
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
	
	private function createPopover(string $id, string $headText, string $bodyText, string $popoverstate = popover_state::AUTO): Dialog {
		$_dlg = new Dialog();
		$_dlg->setPopover($popoverstate);
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
	//
	// Each tab radio writes the active tab id to a cookie on change so PHP
	// can restore it server-side on the next load via setActiveTab().
	// The checked state is driven by $this->activeTab (default 'tab-login').
	
	private function createCardDiv(): Div {
		$_card = new Div();
		$_card->addClass('card');
		
		// Radio inputs for CSS tab control
		foreach (array_keys($this->tabProps) as $_id) {
			$_radio = new Input(form_input_type::RADIOBTN);
			$_radio->setName('tab');
			$_radio->setId($_id);
			$_radio->addEvent(
				input_events::CHANGE,
				"document.cookie='active_tab='+this.id+';path=/;max-age=31536000';"
				);
			if ($_id === $this->activeTab)
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
		
		if ($this->pageContract->error !== null)
			$_card->addContent($this->createPopover(
				'global_error',
				'⚠ NOTICE',
				$this->pageContract->error
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
		
		if ($this->pageContract->csrfToken === '') {
			$_page->setInert();
			
			$_page->addContent($this->createPopover(
				'fatal_error',
				'⚠ ERROR',
				'Session error, please refresh page.',
				popover_state::MANUAL
			));
			
			if (IncidentReporter::isAvailable()) IncidentReporter::report('AuthView', 'Missing csrf token');
		}
		
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
		$_page = new Html('LuandaVAT — private access');
		
		if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']))
			$_page->setBaseUrl('https://localhost/LuandaVAT/');
		else
			$_page->setBaseUrl('https://www.luandavat.co.uk/');
		
		$_page->setFavIcon('favicon.svg', image_mime_types::SVG);
		
		$_page->addStylesheet('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Cinzel:wght@400;500&family=Bodoni+Moda:ital,wght@0,400;0,500;1,400&family=Raleway:wght@300;400;500&display=swap');
		$_page->addStylesheet('public/css/style.css?ver=' . time());
		$_page->addStylesheet('public/css/theme-switch.css?ver=' . time());
		
		$_page->setupMobile();
		$_page->setupFontAwesome();
		
		$_page->addContent($this->createPageDiv());
		
		if ($this->pageContract->error !== null)
			$_page->addScript(script_type::RUNCMD, 'global_error.showPopover();');
		
		if ($this->pageContract->csrfToken === '')
			$_page->addScript(script_type::RUNCMD, 'fatal_error.showPopover();');
		
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
	public const TEXT		= 'eye';
	public const PWD		= 'eye-slash';
	public const LIGHT		= 'sun';
	public const DARK		= 'moon';
}

class form_tabs {
	public const LOGIN		= 'tab-login';
	public const REGISTER	= 'tab-register';
	public const DELETE		= 'tab-delete';
}

// ── Field state & form contracts ──────────────────────────────
//
// FieldState is the unit of data the controller writes per field:
//   $view->loginContract->email->value = 'foo@bar.com';
//   $view->loginContract->email->error = 'Invalid address';
//
// Checkbox fields carry only a bool — they cannot be "faulty".
//
// PageContract is page-level: the CSRF token (required, written by every
// controller before render) and an optional business-logic error string
// that triggers the global_error popover.  Field-validation errors never
// touch pageContract->error; they go directly to the relevant FieldState.
//
// Contracts are instantiated on AuthView construction so the controller
// always finds them ready, even if it sets nothing.

class FieldState {
	public string $value	= '';
	public string $error	= '';
}

class PageContract {
	public string  $csrfToken	= '';
	public ?string $error		= null;
}

class LoginContract {
	public FieldState $email;
	public FieldState $password;
	public bool $remember = false;
	
	public function __construct() {
		$this->email	= new FieldState();
		$this->password	= new FieldState();
	}
}

class RegisterContract {
	public FieldState $name;
	public FieldState $email;
	public FieldState $password;
	
	public function __construct() {
		$this->name		= new FieldState();
		$this->email	= new FieldState();
		$this->password	= new FieldState();
	}
}

class DeleteContract {
	public FieldState $email;
	public FieldState $password;
	public bool $send_data = false;
	
	public function __construct() {
		$this->email	= new FieldState();
		$this->password	= new FieldState();
	}
}

?>