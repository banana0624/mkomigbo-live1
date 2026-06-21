<?php
// Run this on the server: php write_subject_svgs.php
$dir = '/home/mkomigbo/public_html/assets/images/subjects/';

$subjects = [

'history' => ['bg'=>'#1a2744','accent'=>'#c4a35a','symbol'=>'
  <rect x="176" y="140" width="160" height="200" rx="8" fill="none" stroke="#c4a35a" stroke-width="10"/>
  <line x1="210" y1="185" x2="302" y2="185" stroke="#c4a35a" stroke-width="8" stroke-linecap="round"/>
  <line x1="210" y1="215" x2="302" y2="215" stroke="#c4a35a" stroke-width="8" stroke-linecap="round"/>
  <line x1="210" y1="245" x2="280" y2="245" stroke="#c4a35a" stroke-width="8" stroke-linecap="round"/>
  <circle cx="316" cy="316" r="52" fill="none" stroke="#c4a35a" stroke-width="10"/>
  <line x1="316" y1="280" x2="316" y2="318" stroke="#c4a35a" stroke-width="8" stroke-linecap="round"/>
  <line x1="316" y1="318" x2="340" y2="342" stroke="#c4a35a" stroke-width="8" stroke-linecap="round"/>
'],

'slavery' => ['bg'=>'#2a1a1a','accent'=>'#b85c5c','symbol'=>'
  <circle cx="256" cy="200" r="56" fill="none" stroke="#b85c5c" stroke-width="10"/>
  <line x1="256" y1="256" x2="256" y2="340" stroke="#b85c5c" stroke-width="10" stroke-linecap="round"/>
  <line x1="216" y1="290" x2="296" y2="290" stroke="#b85c5c" stroke-width="10" stroke-linecap="round"/>
  <line x1="220" y1="340" x2="292" y2="340" stroke="#b85c5c" stroke-width="10" stroke-linecap="round"/>
  <path d="M180 160 Q256 120 332 160" fill="none" stroke="#b85c5c" stroke-width="6" stroke-dasharray="12,8"/>
'],

'people' => ['bg'=>'#1a2a1a','accent'=>'#5cb85c','symbol'=>'
  <circle cx="176" cy="200" r="44" fill="none" stroke="#5cb85c" stroke-width="9"/>
  <circle cx="336" cy="200" r="44" fill="none" stroke="#5cb85c" stroke-width="9"/>
  <circle cx="256" cy="176" r="44" fill="none" stroke="#5cb85c" stroke-width="9"/>
  <path d="M120 340 Q176 280 232 320" fill="none" stroke="#5cb85c" stroke-width="9" stroke-linecap="round"/>
  <path d="M280 320 Q336 280 392 340" fill="none" stroke="#5cb85c" stroke-width="9" stroke-linecap="round"/>
  <path d="M200 360 Q256 300 312 360" fill="none" stroke="#5cb85c" stroke-width="9" stroke-linecap="round"/>
'],

'persons' => ['bg'=>'#1a1a2a','accent'=>'#7c7ccc','symbol'=>'
  <circle cx="256" cy="185" r="60" fill="none" stroke="#7c7ccc" stroke-width="10"/>
  <path d="M156 360 Q190 280 256 265 Q322 280 356 360" fill="none" stroke="#7c7ccc" stroke-width="10" stroke-linecap="round"/>
  <line x1="256" y1="150" x2="256" y2="130" stroke="#7c7ccc" stroke-width="8" stroke-linecap="round"/>
  <line x1="226" y1="158" x2="210" y2="143" stroke="#7c7ccc" stroke-width="8" stroke-linecap="round"/>
  <line x1="286" y1="158" x2="302" y2="143" stroke="#7c7ccc" stroke-width="8" stroke-linecap="round"/>
'],

'culture' => ['bg'=>'#2a1a2a','accent'=>'#cc7ccc','symbol'=>'
  <path d="M256 140 L290 220 L380 220 L310 270 L336 356 L256 305 L176 356 L202 270 L132 220 L222 220 Z" fill="none" stroke="#cc7ccc" stroke-width="9" stroke-linejoin="round"/>
  <circle cx="256" cy="256" r="30" fill="#cc7ccc" opacity=".6"/>
'],

'religion' => ['bg'=>'#1a1f2a','accent'=>'#f0c060','symbol'=>'
  <line x1="256" y1="130" x2="256" y2="370" stroke="#f0c060" stroke-width="10" stroke-linecap="round"/>
  <line x1="180" y1="205" x2="332" y2="205" stroke="#f0c060" stroke-width="10" stroke-linecap="round"/>
  <circle cx="256" cy="256" r="90" fill="none" stroke="#f0c060" stroke-width="6" stroke-dasharray="18,12"/>
'],

'spirituality' => ['bg'=>'#0d1a2a','accent'=>'#60b0f0','symbol'=>'
  <circle cx="256" cy="256" r="100" fill="none" stroke="#60b0f0" stroke-width="8"/>
  <circle cx="256" cy="256" r="60" fill="none" stroke="#60b0f0" stroke-width="6"/>
  <circle cx="256" cy="256" r="20" fill="#60b0f0"/>
  <line x1="256" y1="136" x2="256" y2="156" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="256" y1="356" x2="256" y2="376" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="136" y1="256" x2="156" y2="256" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="356" y1="256" x2="376" y2="256" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="171" y1="171" x2="185" y2="185" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="327" y1="327" x2="341" y2="341" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="341" y1="171" x2="327" y2="185" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
  <line x1="185" y1="327" x2="171" y2="341" stroke="#60b0f0" stroke-width="6" stroke-linecap="round"/>
'],

'tradition' => ['bg'=>'#1f1a0d','accent'=>'#d4a020','symbol'=>'
  <path d="M256 140 L200 200 L200 340 L312 340 L312 200 Z" fill="none" stroke="#d4a020" stroke-width="9"/>
  <path d="M176 200 L256 140 L336 200" fill="none" stroke="#d4a020" stroke-width="9" stroke-linejoin="round"/>
  <rect x="232" y="270" width="48" height="70" rx="4" fill="none" stroke="#d4a020" stroke-width="8"/>
  <line x1="216" y1="248" x2="216" y2="268" stroke="#d4a020" stroke-width="7" stroke-linecap="round"/>
  <line x1="296" y1="248" x2="296" y2="268" stroke="#d4a020" stroke-width="7" stroke-linecap="round"/>
'],

'language1' => ['bg'=>'#0d1f2a','accent'=>'#30b8c8','symbol'=>'
  <rect x="150" y="150" width="212" height="150" rx="12" fill="none" stroke="#30b8c8" stroke-width="9"/>
  <path d="M256 300 L256 340 L220 300" fill="none" stroke="#30b8c8" stroke-width="9" stroke-linejoin="round"/>
  <line x1="186" y1="200" x2="326" y2="200" stroke="#30b8c8" stroke-width="7" stroke-linecap="round"/>
  <line x1="186" y1="232" x2="296" y2="232" stroke="#30b8c8" stroke-width="7" stroke-linecap="round"/>
  <line x1="186" y1="264" x2="310" y2="264" stroke="#30b8c8" stroke-width="7" stroke-linecap="round"/>
'],

'language2' => ['bg'=>'#0d2a1f','accent'=>'#30c870','symbol'=>'
  <path d="M180 200 Q256 150 332 200 Q332 300 256 340 Q180 300 180 200 Z" fill="none" stroke="#30c870" stroke-width="9"/>
  <line x1="256" y1="155" x2="256" y2="340" stroke="#30c870" stroke-width="6" stroke-dasharray="10,8"/>
  <line x1="180" y1="248" x2="332" y2="248" stroke="#30c870" stroke-width="6" stroke-dasharray="10,8"/>
  <line x1="196" y1="196" x2="316" y2="196" stroke="#30c870" stroke-width="6" stroke-dasharray="10,8"/>
  <line x1="196" y1="300" x2="316" y2="300" stroke="#30c870" stroke-width="6" stroke-dasharray="10,8"/>
'],

'struggles' => ['bg'=>'#2a1a0d','accent'=>'#e07820','symbol'=>'
  <path d="M180 340 L180 220 L256 160 L332 220 L332 340" fill="none" stroke="#e07820" stroke-width="9" stroke-linejoin="round"/>
  <line x1="140" y1="340" x2="372" y2="340" stroke="#e07820" stroke-width="9" stroke-linecap="round"/>
  <path d="M256 160 L256 130 L280 108" fill="none" stroke="#e07820" stroke-width="7" stroke-linecap="round"/>
  <circle cx="292" cy="100" r="16" fill="#e07820"/>
'],

'biafra' => ['bg'=>'#0a1a0a','accent'=>'#50c850','symbol'=>'
  <circle cx="256" cy="256" r="110" fill="none" stroke="#50c850" stroke-width="9"/>
  <path d="M186 186 L326 326" stroke="#50c850" stroke-width="6" stroke-linecap="round"/>
  <path d="M326 186 L186 326" stroke="#50c850" stroke-width="6" stroke-linecap="round"/>
  <circle cx="256" cy="180" r="16" fill="#50c850"/>
  <circle cx="256" cy="332" r="16" fill="#50c850"/>
  <circle cx="180" cy="256" r="16" fill="#50c850"/>
  <circle cx="332" cy="256" r="16" fill="#50c850"/>
  <circle cx="256" cy="256" r="28" fill="none" stroke="#50c850" stroke-width="8"/>
'],

'nigeria' => ['bg'=>'#0a1f0a','accent'=>'#40a840','symbol'=>'
  <rect x="170" y="150" width="172" height="220" rx="6" fill="none" stroke="#40a840" stroke-width="9"/>
  <rect x="170" y="150" width="57" height="220" fill="#40a840" opacity=".7"/>
  <rect x="285" y="150" width="57" height="220" fill="#40a840" opacity=".7"/>
  <line x1="256" y1="200" x2="256" y2="320" stroke="#fff" stroke-width="6" stroke-linecap="round" opacity=".5"/>
'],

'resistance' => ['bg'=>'#1a0a0a','accent'=>'#e04040','symbol'=>'
  <path d="M256 140 L340 280 L172 280 Z" fill="none" stroke="#e04040" stroke-width="10" stroke-linejoin="round"/>
  <line x1="256" y1="280" x2="256" y2="360" stroke="#e04040" stroke-width="10" stroke-linecap="round"/>
  <line x1="216" y1="320" x2="296" y2="320" stroke="#e04040" stroke-width="10" stroke-linecap="round"/>
  <circle cx="256" cy="200" r="20" fill="#e04040"/>
'],

'africa' => ['bg'=>'#0a1a0a','accent'=>'#f0a030','symbol'=>'
  <path d="M220 145 L280 145 L310 175 L320 220 L310 260 L290 290 L270 360 L256 375 L242 360 L222 290 L202 260 L192 220 L202 175 Z" fill="none" stroke="#f0a030" stroke-width="9" stroke-linejoin="round"/>
  <circle cx="270" cy="190" r="12" fill="#f0a030"/>
'],

'uk' => ['bg'=>'#0a0a2a','accent'=>'#6080e0','symbol'=>'
  <circle cx="256" cy="256" r="110" fill="none" stroke="#6080e0" stroke-width="9"/>
  <line x1="146" y1="256" x2="366" y2="256" stroke="#6080e0" stroke-width="9"/>
  <line x1="256" y1="146" x2="256" y2="366" stroke="#6080e0" stroke-width="9"/>
  <line x1="178" y1="178" x2="334" y2="334" stroke="#6080e0" stroke-width="5" stroke-dasharray="12,8"/>
  <line x1="334" y1="178" x2="178" y2="334" stroke="#6080e0" stroke-width="5" stroke-dasharray="12,8"/>
'],

'europe' => ['bg'=>'#0a0a1f','accent'=>'#8090d8','symbol'=>'
  <circle cx="256" cy="256" r="110" fill="none" stroke="#8090d8" stroke-width="8"/>
  <circle cx="256" cy="256" r="60" fill="none" stroke="#8090d8" stroke-width="6" stroke-dasharray="14,10"/>
  <path d="M256 146 Q290 200 256 256 Q222 200 256 146" fill="none" stroke="#8090d8" stroke-width="6"/>
  <line x1="146" y1="256" x2="366" y2="256" stroke="#8090d8" stroke-width="6"/>
'],

'arabs' => ['bg'=>'#1f1a0a','accent'=>'#d8b840','symbol'=>'
  <path d="M256 145 C190 145 145 195 145 256 C145 317 190 367 256 367 C322 367 367 317 367 256" fill="none" stroke="#d8b840" stroke-width="9"/>
  <path d="M256 145 L280 175 L256 165 L232 175 Z" fill="#d8b840"/>
  <path d="M200 200 Q256 170 312 200 Q312 290 256 320 Q200 290 200 200 Z" fill="none" stroke="#d8b840" stroke-width="6" stroke-dasharray="10,8"/>
'],

'about' => ['bg'=>'#1a1a1a','accent'=>'#a0a0a0','symbol'=>'
  <circle cx="256" cy="200" r="28" fill="#a0a0a0"/>
  <rect x="232" y="250" width="48" height="110" rx="8" fill="none" stroke="#a0a0a0" stroke-width="9"/>
  <line x1="220" y1="250" x2="292" y2="250" stroke="#a0a0a0" stroke-width="9" stroke-linecap="round"/>
'],

];

foreach ($subjects as $slug => $data) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" role="img" aria-label="{$slug}">
  <circle cx="256" cy="256" r="240" fill="{$data['bg']}"/>
  <circle cx="256" cy="256" r="240" fill="none" stroke="#000" stroke-opacity=".15" stroke-width="8"/>
  {$data['symbol']}
</svg>
SVG;
    file_put_contents($dir . $slug . '.svg', trim($svg));
    echo "Written: $slug.svg\n";
}
echo "All done.\n";