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
use TamasVarga\LuandaPHP\Button;
use TamasVarga\LuandaPHP\form_button_type;
use TamasVarga\LuandaPHP\Textarea;
use TamasVarga\LuandaPHP\Select;
use TamasVarga\LuandaPHP\SelectOption;
use TamasVarga\LuandaPHP\input_events;
use TamasVarga\LuandaPHP\Faicon;

class ContactView {
	
	public function __construct(string $theme = 'dark') {
		$this->theme = $theme;
		
		Element::Beautify();
	}

	// ── Topic options ─────────────────────────────────────────

	private array $topics = [
		''            => 'SELECT A TOPIC',
		'general'     => 'General Enquiry',
		'billing'     => 'Billing & Payments',
		'technical'   => 'Technical Support',
		'partnership' => 'Partnership',
		'other'       => 'Other',
	];

	// ── Theme switch (same as AuthView) ──────────────────────

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
		$_taglineDiv->addContent(new Text('get in touch'));

		$_ornamentDiv = new Div();
		$_ornamentDiv->addClass('ornament');
		$_ornamentDiv->addContent(new Span());

		$_logoDiv->addContent($_logoSvg);
		$_logoDiv->addContent($_brandDiv);
		$_logoDiv->addContent($_taglineDiv);
		$_logoDiv->addContent($_ornamentDiv);

		return $_logoDiv;
	}

	// ── Shared field builders ─────────────────────────────────

	private function buildInputField(string $type, string $id, string $name, string $labelText, int $minLen, int $maxLen, string $autocomplete = ''): Div {
		$_div = new Div();
		$_div->addClass('field');

		$_inp = new Input($type);
		$_inp->setPlaceholder(' ');
		$_inp->setId($id);
		$_inp->setName($name);
		$_inp->toRequired();
		$_inp->setMinMaxLen($minLen, $maxLen);
		if ($autocomplete !== '')
			$_inp->addAutocompletes($autocomplete);

		$_lbl = new Label();
		$_lbl->setInput($id);
		$_lbl->addContent(new Text($labelText));

		$_div->addContent($_inp);
		$_div->addContent($_lbl);

		return $_div;
	}

	private function buildSelectField(string $id, string $name, string $labelText, array $options): Div {
		$_div = new Div();
		$_div->addClass('field field--select');

		$_sel = new Select();
		$_sel->setId($id);
		$_sel->setName($name);
		$_sel->setRequired();

		foreach ($options as $_val => $_display) {
			$_opt = new SelectOption($_val, $_display);
			if ($_val === '') {
				$_opt->Disable();
				$_opt->Select();
			}
			$_sel->addOption($_opt);
		}

		$_lbl = new Label();
		$_lbl->setInput($id);
		$_lbl->addContent(new Text($labelText));

		$_div->addContent($_sel);
		$_div->addContent($_lbl);
		
		return $_div;
	}

	private function buildTextareaField(
		string $id,
		string $name,
		string $labelText,
		int $maxLen = 1000
	): Div {
		$_div = new Div();
		$_div->addClass('field');

		$_ta = new Textarea();
		$_ta->setId($id);
		$_ta->setName($name);
		$_ta->setPlaceholder(' ');
		$_ta->toRequired();
		$_ta->addAttr('maxlength', (string)$maxLen);
		$_ta->addAttr('rows', '4');
		// Character counter driven by inline JS; oninput updates the <span>
		$_ta->addAttr(
			'oninput',
			"(function(t){"
			. "var c=document.getElementById('{$id}_count'),"
			. "r={$maxLen}-t.value.length;"
			. "c.textContent=r;"
			. "c.className='field__counter'"
			. "+(r<50?' is-near':'')+(r<10?' is-limit':'');"
			. "})(this)"
		);

		$_lbl = new Label();
		$_lbl->setInput($id);
		$_lbl->addContent(new Text($labelText));

		$_counter = new Span();
		$_counter->setId("{$id}_count");
		$_counter->addClass('field__counter');
		$_counter->addContent(new Text((string)$maxLen));

		$_div->addContent($_ta);
		$_div->addContent($_lbl);
		$_div->addContent($_counter);

		return $_div;
	}

	// ── Contact form ──────────────────────────────────────────

	private function createContactForm(): Form {
		$_form = new Form();
		$_form->setId('contact_form');
		$_form->addClass('form-panel');   // no tab switching — always visible
		// Override position:absolute from .form-panel for single-form layout
		$_form->addAttr('style', 'position:relative;opacity:1;pointer-events:all;transform:none;');

		$_note = new Div();
		$_note->addClass('note');
		$_note->addContent(new Text('We respond within one business day.'));

		$_btnText = new Span();
		$_btnText->addContent(new Text('SEND MESSAGE'));
		
		$_btn = new Button(form_button_type::BTN);
		$_btn->addClass('btn');
		$_btn->addContent($_btnText);

		$_form->addContent($this->buildInputField(
			form_input_type::TEXT, 'c_name', 'contact_name',
			'FULL NAME', 2, 40, 'name'
		));
		$_form->addContent($this->buildInputField(
			form_input_type::EMAIL, 'c_email', 'contact_email',
			'EMAIL ADDRESS', 6, 40, 'email'
		));
		$_form->addContent($this->buildSelectField(
			'c_topic', 'contact_topic',
			'TOPIC', $this->topics
		));
		$_form->addContent($this->buildTextareaField(
			'c_message', 'contact_message',
			'YOUR MESSAGE', 1000
		));
		$_form->addContent($_btn);
		$_form->addContent($_note);

		return $_form;
	}

	// ── Forms wrapper ─────────────────────────────────────────
	// Single form, no tabs — .forms still used for consistent card padding

	private function createFormsDiv(): Div {
		$_div = new Div();
		$_div->addClass('forms');
		// No min-height override needed; content drives height naturally
		$_div->addAttr('style', 'min-height:0;');
		$_div->addContent($this->createContactForm());
		return $_div;
	}

	// ── Card ─────────────────────────────────────────────────

	private function createCardDiv(): Div {
		$_card = new Div();
		$_card->addClass('card');

		$_card->addContent($this->createLogoDiv());
		$_card->addContent($this->createFormsDiv());

		$_powered = new Div();
		$_powered->addClass('powered');
		$_powered->addContent(new Text('Powered by LuandaPHP 2.1.0'));
		$_card->addContent($_powered);

		return $_card;
	}

	// ── Wrapper ───────────────────────────────────────────────

	private function createWrapDiv(): Div {
		$_wrap = new Div();
		$_wrap->addClass('wrapper');
		$_wrap->addContent($this->createCardDiv());
		return $_wrap;
	}

	// ── Page fixed frame ──────────────────────────────────────

	private function createPageDiv(string $defaultTheme = 'dark'): Div {
		$_page = new Div();
		$_page->addClass('page');
		$_page->addAttr('data-theme', $this->theme);

		[$_switchChk, $_switchLbl] = $this->createThemeSwitch();
		$_page->addContent($_switchChk);
		$_page->addContent($_switchLbl);

		$_page->addContent($this->createWrapDiv());

		return $_page;
	}

	// ── Page entry point ──────────────────────────────────────

	public function createPage(): Html {
		$_page = new Html('LuandaVAT - Contact Us');

		$_page->addStylesheet('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Cinzel:wght@400;500&family=Bodoni+Moda:ital,wght@0,400;0,500;1,400&family=Raleway:wght@300;400;500&display=swap');
		$_page->addStylesheet('css/style.css?ver=' . time());
		$_page->addStylesheet('css/theme-switch.css?ver=' . time());

		$_page->setupMobile();
		$_page->setupFontAwesome();

		$_page->addContent($this->createPageDiv('dark'));

		return $_page;
	}

	// ── SVG (shared with AuthView) ────────────────────────────

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
