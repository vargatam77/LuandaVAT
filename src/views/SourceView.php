<?php
declare(strict_types=1);

namespace LuandaVAT\views;

use TamasVarga\LuandaPHP\Html;
use TamasVarga\LuandaPHP\Text;
use TamasVarga\LuandaPHP\Pre;
use TamasVarga\LuandaPHP\Input;
use TamasVarga\LuandaPHP\form_input_type;
use TamasVarga\LuandaPHP\input_events;
use TamasVarga\LuandaPHP\Span;
use TamasVarga\LuandaPHP\Faicon;
use TamasVarga\LuandaPHP\Label;
use TamasVarga\LuandaPHP\Div;
use TamasVarga\LuandaPHP\Element;
use TamasVarga\LuandaPHP\script_type;
use TamasVarga\LuandaPHP\Code;

class SourceView {
	private ?string $theme = null;
	
	public function __construct(string $theme = 'dark') {
		$this->theme = $theme;
		
		Element::Beautify();
	}
	
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
		$_wrap->addClass('code');
		
		$_text = new Text('');
		$_text->getFromUrl(__DIR__ . '/AuthView.php');
		
		$_code = new Code();
		$_code->addClass('language-php');
		
		$_code->addContent($_text);
		
		$_pre = new Pre();
		$_pre->addContent($_code);
		
		$_wrap->addContent($_pre);
		
		return $_wrap;
	}
	
	public function createPage(): Html {
		$_page = new Html('AuthView source code');
		
		$_page->addStylesheet('css/style.css?ver=' . time());
		$_page->addStylesheet('css/theme-switch.css?ver=' . time());
		
		if ($this->theme === 'dark')
			$_page->addStylesheet('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css');
		else
			$_page->addStylesheet('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-light.min.css');
		
		$_page->addScript(script_type::HEADLINK, 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js');
		$_page->addScript(script_type::RUNCMD, 'hljs.highlightAll();');
		
		$_page->setupMobile();
		$_page->setupFontAwesome();
		
		$_page->addContent($this->createPageDiv());
		
		return $_page;
	}
}

?>