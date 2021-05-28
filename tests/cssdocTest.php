<?php
use hexydec\css\cssdoc;

final class cssdocTest extends \PHPUnit\Framework\TestCase {

	protected $config = [
		'semicolons' => false, // remove last semi-colon in each rule
		'zerounits' => false, // remove the unit from 0 values where possible (0px => 0)
		'leadingzeros' => false, // remove leading 0 from fractional values (0.5 => .5)
		'trailingzeros' => false, // remove any trailing 0's from fractional values (74.0 => 74)
		'decimalplaces' => null, // maximum number of decimal places for a value
		'quotes' => false, // remove quotes where possible (background: url("test.png") => background: url(test.png))
		'convertquotes' => false, // convert single quotes to double quotes (content: '' => content: "")
		'colors' => false, // shorten hex values and replace with named values where shorter (color: #FF0000 => color: red)
		'time' => false, // shorten time values where possible (500ms => .5s)
		'fontweight' => false, // shorten font-weight values (font-weight: bold => font-weight: 700)
		'none' => false, // replace none with 0 where possible (border: none => border: 0)
		'lowerproperties' => false, // lowercase property names (DISPLAY: BLOCK => display: BLOCK)
		'lowervalues' => false // lowercase values where possible (DISPLAY: BLOCK => DISPLAY: block)
	];

	public function testCanMinifyCss() {
		$tests = [
			[
				'input' => '#id {
					font-size: 3em;
				}',
				'output' => '#id{font-size:3em;}'
			],
			[
				'input' => '#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}',
				'output' => '#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			],
			[
				'input' => '#id {
					font-size: 3em;
				}

				#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
					-webkit-display: block;
					font-family: "segoe UI", Verdana, Arial, sans-serif;
				}
				',
				'output' => '#id{font-size:3em;}#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;-webkit-display:block;font-family:"segoe UI",Verdana,Arial,sans-serif;}'
			],
			[
				'input' => '
					#id {
						font-size: 3em !important;
					}
				',
				'output' => '#id{font-size:3em!important;}'
			],
			[
				'input' => '
					* {
						display: block;
					}
				',
				'output' => '*{display:block;}'
			],
			[
				'input' => '
					*, :before, ::after {
						display: block;
					}
				',
				'output' => '*,:before,::after{display:block;}'
			],
			[
				'input' => '
					#id {
						width: calc(50% + 20px);
					}
				',
				'output' => '#id{width:calc(50% + 20px);}'
			]
		];
		$this->compareMinify($tests, $this->config);

		// test importing file
		$obj = new cssdoc(['colors' => ['#639' => 'rebeccapurple']]);
		if ($obj->open(__DIR__.'/templates/css.css')) {
			$obj->minify();
			$minified = trim(file_get_contents(__DIR__.'/templates/css-minified.css'));
			$this->assertEquals($minified, $obj->compile(), 'Can minify CSS');

			$obj->load($minified);
			$obj->minify();
			$this->assertEquals($minified, $obj->compile());

			// test save method
			$this->assertEquals($minified, $obj->save());
			$file = __DIR__.'/test.css';
			$this->assertEquals(true, $obj->save($file, ['style' => 'beautify']));
			$this->assertEquals(true, file_exists($file));
			unlink($file);
		}
	}

	public function testCanMinifyUrls() {
		$tests = [
			[
				'input' => '#id {
					background-image: url(test.png);
				}',
				'output' => '#id{background-image:url(test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(folder/test.png);
				}',
				'output' => '#id{background-image:url(folder/test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(/folder/test.png);
				}',
				'output' => '#id{background-image:url(/folder/test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(https://github.com/hexydec/cssdoc/test.png);
				}',
				'output' => '#id{background-image:url(https://github.com/hexydec/cssdoc/test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(../test.png);
				}',
				'output' => '#id{background-image:url(../test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(../../test.png);
				}',
				'output' => '#id{background-image:url(../../test.png);}'
			],
			[
				'input' => '#id {
					background-image: url(../../folder/test.png);
				}',
				'output' => '#id{background-image:url(../../folder/test.png);}'
			]
		];
		$this->compareMinify($tests, $this->config);
	}

	public function testCanMinifyAtRules() {
		$tests = [
			[
				'input' => '@media screen {
					#id {
						font-size: 3em;
					}
				}

				#id, .class, .class .class__item, .class > .class__item {
					font-size: 3em;
					display: flex;
				}
				',
				'output' => '@media screen{#id{font-size:3em;}}#id,.class,.class .class__item,.class>.class__item{font-size:3em;display:flex;}'
			],
			[
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-size:3em;}}'
			],
			[
				'input' => '/* Starts with a comment */

				@media screen and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-size:3em;}}'
			],
			[
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-size:3em;}}'
			],
			[
				'input' => '@media screen, print and ( max-width : 800px ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media screen,print and (max-width:800px){#id{font-size:3em;}}'
			],
			[
				'input' => '@media ( color ) {
					#id {
						font-size: 3em;
					}
				}
				',
				'output' => '@media (color){#id{font-size:3em;}}'
			],
			[
				'input' => '@supports (display: grid) {
					  div {
					    display: grid;
					  }
					}',
				'output' => '@supports (display:grid){div{display:grid;}}'
			],
			[
				'input' => '@supports not (display: grid) {
					  div {
					    float: right;
					  }
					}',
				'output' => '@supports not(display:grid){div{float:right;}}'
			],
			[
				'input' => '@page {
								margin: 1cm;
							}',
				'output' => '@page{margin:1cm;}'
			],
			[
				'input' => '@page :first {
								margin: 1cm;
							}',
				'output' => '@page :first{margin:1cm;}'
			]
		];
		$this->compareMinify($tests, $this->config);
	}

	public function testCanMinifyDirectives() {
		$tests = [
			[
				'input' => '@charset   "utf-8"   ;',
				'output' => '@charset "utf-8";'
			],
			[
				'input' => '@font-face {
					font-family: "gotham";
					src: url(".../css/gotham-medium.woff2") format("woff2"),
						url("../css/gotham/gotham-medium.woff") format("woff");
					font-display: block;
				}
				',
				'output' => '@font-face{font-family:"gotham";src:url(".../css/gotham-medium.woff2") format("woff2"),url("../css/gotham/gotham-medium.woff") format("woff");font-display:block;}'
			],
			[
				'input' => '@import url("fineprint.css") print;
					@import url("bluish.css") speech;
					@import \'custom.css\';
					@import url("chrome://communicator/skin/");
					@import "common.css" screen;
					@import url(\'landscape.css\') screen and (orientation: landscape);
				',
				'output' => '@import url("fineprint.css") print;@import url("bluish.css") speech;@import \'custom.css\';@import url("chrome://communicator/skin/");@import "common.css" screen;@import url(\'landscape.css\') screen and (orientation:landscape);'
			],
			[
				'input' => '@page {
						margin: 1cm;
					}

					@page :first {
						margin: 2cm;
					}
				',
				'output' => '@page{margin:1cm;}@page :first{margin:2cm;}'
			],
			[
				'input' => '@keyframes slidein {
					from {
				    	transform: translateX(0%);
					}

				  	to {
					  	transform: translateX(100%);
					}
				}
				',
				'output' => '@keyframes slidein{from{transform:translateX(0%);}to{transform:translateX(100%);}}'
			]
		];
		$this->compareMinify($tests, $this->config);
	}

	public function testCanRemoveLastSemicolon() {
		$tests = [
			[
				'input' => '#id {
					font-family: Arial, sans-serif;
					font-size: 3em;
				}',
				'output' => '#id{font-family:Arial,sans-serif;font-size:3em}'
			]
		];
		$config = $this->config;
		$config['semicolons'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanRemoveZeroUnits() {
		$tests = [
			[
				'input' => '#id {
					margin: 0px 0% 20px 0em;
				}
				.class {
					transition: all 500ms;
					transition-delay: 0s;
				}',
				'output' => '#id{margin:0 0 20px 0;}.class{transition:all 500ms;transition-delay:0s;}'
			],
			[
				'input' => '#id {
					background-color: linear-gradient(red 0%, blue 100%);
					transform: rotate(0deg);
				}
				@keyframes spin {
					0% {
						transform: rotate(0deg);
					}
					to {
						transform: rotate(60deg);
					}
				}',
				'output' => '#id{background-color:linear-gradient(red 0%,blue 100%);transform:rotate(0deg);}@keyframes spin{0%{transform:rotate(0deg);}to{transform:rotate(60deg);}}'
			]
		];
		$config = $this->config;
		$config['zerounits'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanRemoveLeadingZeros() {
		$tests = [
			[
				'input' => '#id {
					font-size: 0.9em;
					transition: all 0000.5s;
				}',
				'output' => '#id{font-size:.9em;transition:all .5s;}'
			]
		];
		$config = $this->config;
		$config['leadingzeros'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanRemoveTrailingZeros() {
		$tests = [
			[
				'input' => '#id {
					font-size: 14.0em;
					transition: all 500ms;
					transition-delay: 3.2000s;
					padding: 32.5000px;
				}',
				'output' => '#id{font-size:14em;transition:all 500ms;transition-delay:3.2s;padding:32.5px;}'
			]
		];
		$config = $this->config;
		$config['trailingzeros'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanReduceDecimalPlaces() {
		$tests = [
			[
				'input' => '#id {
					font-size: 0.983872384882657em;
					width: 33.3333333333333333%;
				}',
				'output' => '#id{font-size:0.9838em;width:33.3333%;}'
			]
		];
		$config = $this->config;
		$config['decimalplaces'] = 4;
		$this->compareMinify($tests, $config);

		// check decimalplaces = 0;
		$tests = [
			[
				'input' => '#id {
					font-size: 0.983872384882657em;
					width: 33.3333333333333333%;
				}',
				'output' => '#id{font-size:0em;width:33%;}'
			]
		];
		$config['decimalplaces'] = 0;
		$this->compareMinify($tests, $config);
	}

	public function testCanRemoveUnnecessaryQuotes() {
		$tests = [
			[
				'input' => '#id {
					background: url("test.png");
				}',
				'output' => '#id{background:url(test.png);}'
			],
			[
				'input' => "#id {
					background: url('test.png');
				}",
				'output' => '#id{background:url(test.png);}'
			],
			[
				'input' => '#id::before {
					content: "Foo (bar)";
				}',
				'output' => '#id::before{content:"Foo (bar)";}'
			],
			[ // protect content
				'input' => '#id::before {
					content: "Foo";
				}',
				'output' => '#id::before{content:"Foo";}'
			],
			[ // protect format
				'input' => '@font-face {
					font-family: "gotham";
					src: url(".../css/gotham-medium.woff2") format("woff2"),
						url("../css/gotham/gotham-medium.woff") format("woff");
					font-display: block;
				}
				',
				'output' => '@font-face{font-family:gotham;src:url(.../css/gotham-medium.woff2) format("woff2"),url(../css/gotham/gotham-medium.woff) format("woff");font-display:block;}'
			],
			[ // protect counters
				'input' => '#id::before {
					content: counters(item, ".");
				}',
				'output' => '#id::before{content:counters(item,".");}'
			],
			[
				'input' => '@charset   "utf-8"   ;',
				'output' => '@charset "utf-8";'
			],
		];
		$config = $this->config;
		$config['quotes'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanConvertQuotes() {
		$tests = [
			[
				'input' => "#id {
					background: url('test.png');
				}",
				'output' => '#id{background:url("test.png");}'
			],
			[
				'input' => "#id::before {
					content: 'Foo (bar)';
				}",
				'output' => '#id::before{content:"Foo (bar)";}'
			],
			[
				'input' => "#id::before {
					content: 'Foo';
				}",
				'output' => '#id::before{content:"Foo";}'
			]
		];
		$config = $this->config;
		$config['convertquotes'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanShortenHexValues() {
		$tests = [
			[
				'input' => "#id {
					color: #000000;
				}",
				'output' => '#id{color:#000;}'
			],
			[
				'input' => "#id::before {
					color: #FFCCAA;
				}",
				'output' => '#id::before{color:#FCA;}'
			],
			[
				'input' => "#id::before {
					color: #ffccaa;
				}",
				'output' => '#id::before{color:#fca;}'
			],
			[
				'input' => "#id::before {
					color: #ffccab;
				}",
				'output' => '#id::before{color:#ffccab;}'
			],
			[
				'input' => "#id {
					color: #FF0000;
					background: #ffd700;
				}",
				'output' => '#id{color:red;background:gold;}'
			]
		];
		$config = $this->config;
		$config['colors'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanShortenTimeValues() {
		$tests = [
			[
				'input' => '#id {
					transition: all 5ms;
				}',
				'output' => '#id{transition:all 5ms;}'
			],
			[
				'input' => '#id {
					transition: all 50ms;
				}',
				'output' => '#id{transition:all 50ms;}'
			],
			[
				'input' => '#id {
					transition: all 400ms;
				}',
				'output' => '#id{transition:all .4s;}'
			],
			[
				'input' => '#id {
					transition: all 1400ms;
				}',
				'output' => '#id{transition:all 1.4s;}'
			],
			[
				'input' => '#id {
					transition: all 450ms;
				}',
				'output' => '#id{transition:all .45s;}'
			],
			[
				'input' => '#id {
					transition: all 1450ms;
				}',
				'output' => '#id{transition:all 1.45s;}'
			],
			[
				'input' => '#id {
					transition: all 001450ms;
				}',
				'output' => '#id{transition:all 001.45s;}'
			],
			[
				'input' => '#id {
					transition: all 31450ms;
				}',
				'output' => '#id{transition:all 31.45s;}'
			],
			[
				'input' => '#id {
					transition: all 31450MS;
				}',
				'output' => '#id{transition:all 31.45s;}'
			],
			[
				'input' => '#id {
					transition: all 00450ms;
				}',
				'output' => '#id{transition:all 00.45s;}'
			]
		];
		$config = $this->config;
		$config['time'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanShortenFontWeight() {
		$tests = [
			[
				'input' => '#id {
					font-weight: normal;
				}',
				'output' => '#id{font-weight:400;}'
			],
			[
				'input' => '#id {
					font-weight: bold;
				}',
				'output' => '#id{font-weight:700;}'
			],
			[
				'input' => '#id {
					FONT-WEIGHT: NORMAL;
				}',
				'output' => '#id{FONT-WEIGHT:400;}'
			],
			[
				'input' => '#id {
					FONT-WEIGHT: BOLD;
				}',
				'output' => '#id{FONT-WEIGHT:700;}'
			],
			[
				'input' => '#id {
					font-weight: inherit;
				}',
				'output' => '#id{font-weight:inherit;}'
			],
			[
				'input' => '#id {
					font-weight: 100;
				}',
				'output' => '#id{font-weight:100;}'
			],
			[
				'input' => '#id {
					font-style: normal;
				}',
				'output' => '#id{font-style:normal;}'
			]
		];
		$config = $this->config;
		$config['fontweight'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanShortenNone() {
		$tests = [
			[
				'input' => "#id {
					border: none;
					outline: none;
					background: none;
				}",
				'output' => '#id{border:0;outline:0;background:0;}'
			],
			[
				'input' => "#id {
					border: 0 none;
					outline: 1px none; /* is this even valid?? Not that it matters for the purpose of this */
					background: none no-repeat;
				}",
				'output' => '#id{border:0 none;outline:1px none;background:none no-repeat;}'
			]
		];
		$config = $this->config;
		$config['none'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanLowerValues() {
		$tests = [
			[
				'input' => "#id::before {
					color: #FFCCAA;
				}",
				'output' => '#id::before{color:#ffccaa;}'
			],
			[
				'input' => "#id::before {
					color: #FcA;
				}",
				'output' => '#id::before{color:#fca;}'
			],
			[
				'input' => "#id::before {
					color: #FFCCAB;
				}",
				'output' => '#id::before{color:#ffccab;}'
			],
			[
				'input' => '#id::before {
					background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
				}',
				'output' => '#id::before{background:#ffccab url("TEST.PNG") no-repeat 50% top;}'
			],
			[
				'input' => '#id::before {
					background: #FFCCAB URL(TEST.PNG) NO-REPEAT 50% TOP;
				}',
				'output' => '#id::before{background:#ffccab url(TEST.PNG) no-repeat 50% top;}'
			],
			[
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						FONT-WEIGHT: BOLD;
						background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{FONT-WEIGHT:bold;background:#ffccab url("TEST.PNG") no-repeat 50% top;}}'
			],
			[
				'input' => '#id {
					transform: translateX(-50PX);
				}',
				'output' => '#id{transform:translatex(-50px);}' // yes this is legal
			]
		];
		$config = $this->config;
		$config['lowervalues'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanLowerProperties() {
		$tests = [
			[
				'input' => "#id {
					COLOR: #FFCCAA;
				}",
				'output' => '#id{color:#FFCCAA;}'
			],
			[
				'input' => ".camelClass {
					COLOR: #FcA;
					Font-Weight: BOLD;
					Font-STYLE: Italic;
				}",
				'output' => '.camelClass{color:#FcA;font-weight:BOLD;font-style:Italic;}'
			],
			[
				'input' => "@font-face {
					FONT-FAMILY: GOTHAM;
				}",
				'output' => '@font-face{font-family:GOTHAM;}'
			],
			[
				'input' => '#id::before {
					background: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
				}',
				'output' => '#id::before{background:#FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;}'
			],
			[
				'input' => '@media screen and ( max-width : 800px ) {
					#id {
						FONT-WEIGHT: BOLD;
						BACKGROUND: #FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;
					}
				}
				',
				'output' => '@media screen and (max-width:800px){#id{font-weight:BOLD;background:#FFCCAB URL("TEST.PNG") NO-REPEAT 50% TOP;}}'
			]
		];
		$config = $this->config;
		$config['lowerproperties'] = true;
		$this->compareMinify($tests, $config);
	}

	public function testCanHandleDifficultCss() {
		$tests = [
			[
				'input' => "a.awkward\\@class {
					display: block;
				}",
				'output' => 'a.awkward\\@class{display:block}'
			],
			[
				'input' => ".doubleSemi {
					display: block;;
					font-size: 1em;;;;
				}",
				'output' => '.doubleSemi{display:block;font-size:1em}'
			],
			[
				'input' => ".incorrect {
					display: block; !important;
				}",
				'output' => '.incorrect{display:block}'
			],
			[
				'input' => ".data-uri {
					background: url(data:image/svg+xml;base64,PHN2ZyBkYXRhLW5hbWU9IkxheWVyIDEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDIwMCAyMDAiPjxwYXRoIGZpbGw9IiM1NzU3NTYiIGQ9Ik0uMS4zaDIwMHYxOTkuNzdILjF6Ii8+PGNpcmNsZSBjeD0iMTAwIiBjeT0iNjAuNyIgcj0iNTAiIGZpbGw9IiNmZmYiLz48ZWxsaXBzZSBjeD0iMTAwIiBjeT0iMjAwLjMiIHJ4PSI4MCIgcnk9Ijk4LjUiIGZpbGw9IiNmZmYiLz48L3N2Zz4=);
				}",
				'output' => '.data-uri{background:url(data:image/svg+xml;base64,PHN2ZyBkYXRhLW5hbWU9IkxheWVyIDEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDIwMCAyMDAiPjxwYXRoIGZpbGw9IiM1NzU3NTYiIGQ9Ik0uMS4zaDIwMHYxOTkuNzdILjF6Ii8+PGNpcmNsZSBjeD0iMTAwIiBjeT0iNjAuNyIgcj0iNTAiIGZpbGw9IiNmZmYiLz48ZWxsaXBzZSBjeD0iMTAwIiBjeT0iMjAwLjMiIHJ4PSI4MCIgcnk9Ijk4LjUiIGZpbGw9IiNmZmYiLz48L3N2Zz4=)}'
			]
		];
		$this->compareMinify($tests);
	}

	protected function testCanDeleteEmptyDirecctiveAndRules() {
		$tests = [
			[
				'input' => '
					@media screen {}
				',
				'output' => ''
			],
			[
				'input' => '
					@media screen {
						.nothing {}
						.another__nothing {}
					}
				',
				'output' => ''
			],
			[
				'input' => '
					@media screen {
						.something {
							display: block;
						}
						.another__nothing {}
					}
				',
				'output' => '@media screen{.something{display:block;}}'
			],
			[
				'input' => '
					@media screen {
						.nothing {}
						.another__nothing {}
					}
					.something {
						display: block;
					}
				',
				'output' => '.something{display:block}'
			],
			[
				'input' => '
					@page {}
				',
				'output' => ''
			],
			[
				'input' => '
					@page {
						display: block;
					}
				',
				'output' => '@page{display:block}'
			],
		];
		$this->compareMinify($tests);
	}

	protected function compareMinify(array $tests, array $minify = []) {
		$obj = new cssdoc();
		foreach ($tests AS $item) {
			$obj->load($item['input']);

			// minify and check against the output
			$obj->minify($minify);
			$compiled = $obj->compile();
			$this->assertEquals($item['output'], $compiled);

			// recycle the output
			$obj->load($compiled);
			$obj->minify($minify);
			$this->assertEquals($item['output'], $obj->compile());
		}
	}
}
