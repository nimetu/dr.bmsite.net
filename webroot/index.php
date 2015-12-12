<?php

if (isset($_GET['src']) && $_GET['src'] == 'show') {
	echo "<b>Last modified :</b> ".date("Y-m-d H:i:s", filemtime(__FILE__))." UTC<br>\n";
	echo "<div style=\"font-size: 12px;\">\n";
	highlight_string(file_get_contents(__FILE__));
	print "</div>\n";
	exit;
}


if ($_SERVER['HTTP_HOST'] == 'ryapp') {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
} else {
    error_reporting(0);
    ini_set('display_errors', false);
}

// FIXME: import dressing room (or api) url
use Ryzom\Common\EGender;
use Ryzom\Common\EVisualSlot;
use Ryzom\Common\TPeople;

if (!function_exists('_h')) {
    function _h($str)
    {
        return htmlspecialchars($str);
    }
}
function __($sheet, $lang = LANG)
{
    return _h(ryzom_translate($sheet, $lang));
}

require __DIR__ . '/../vendor/autoload.php';

//
// form
//
$form = [
    'language' => is($_REQUEST['language'], is($_GET['lang'], 'en')),
    'zoom' => is($_REQUEST['zoom'], 'body'),
    'dir' => (int) is($_REQUEST['dir'], 0),
    //
    'age' => clamp((int) is($_REQUEST['age'], 0), 0, 2),
    'race' => clamp((int) is($_REQUEST['race'], 0), 0, 3),
    'gender' => clamp((int) is($_REQUEST['gender'], 0), 0, 1),
    'eyes' => clamp((int) is($_REQUEST['eyes'], 0), 0, 6),
    'tattoo' => (int) is($_REQUEST['tattoo'], 0),
    'haircut' => (int) is($_REQUEST['haircut'], 0),
    'haircolor' => (int) is($_REQUEST['haircolor'], 0),
    //
    'chest' => ['item' => (int) is($_REQUEST['slot1']['item'], 0), 'color' => (int) is($_REQUEST['slot1']['color'], 0)],
    'head' => ['item' => (int) is($_REQUEST['slot3']['item'], 0), 'color' => (int) is($_REQUEST['slot3']['color'], 0)],
    'legs' => ['item' => (int) is($_REQUEST['slot2']['item'], 0), 'color' => (int) is($_REQUEST['slot2']['color'], 0)],
    'arms' => ['item' => (int) is($_REQUEST['slot4']['item'], 0), 'color' => (int) is($_REQUEST['slot4']['color'], 0)],
    'hands' => ['item' => (int) is($_REQUEST['slot6']['item'], 0), 'color' => (int) is($_REQUEST['slot6']['color'], 0)],
    'feet' => ['item' => (int) is($_REQUEST['slot7']['item'], 0), 'color' => (int) is($_REQUEST['slot7']['color'], 0)],
    //
    'handr' => (int) is($_REQUEST['slot8'], 0),
    'handl' => (int) is($_REQUEST['slot9'], 0),
    //
    'gabarit' => [
        (int) is($_REQUEST['gabarit'][0], 7),// char height
        (int) is($_REQUEST['gabarit'][1], 7),// torso width
        (int) is($_REQUEST['gabarit'][2], 7),// arms width
        (int) is($_REQUEST['gabarit'][3], 7),// legs width
        (int) is($_REQUEST['gabarit'][4], 7),// breast size
    ],
    'morph' => [
        (int) is($_REQUEST['morph'][0], 3),// morph target 1, uiFace1Fy, uiFace1Ma, uiFace1Tr, uiFace1Zo
        (int) is($_REQUEST['morph'][1], 3),// morph target 2
        (int) is($_REQUEST['morph'][2], 3),// morph target 3
        (int) is($_REQUEST['morph'][3], 3),// morph target 4
        (int) is($_REQUEST['morph'][4], 3),// morph target 5
        (int) is($_REQUEST['morph'][5], 3),// morph target 6
        (int) is($_REQUEST['morph'][6], 3),// morph target 7
        (int) is($_REQUEST['morph'][7], 3),// morph target 8
    ]
];

if (!in_array($form['zoom'], ['body', 'portrait'])) {
    $form['zoom'] = 'body';
}

$form['language'] = (in_array($form['language'], ['en', 'fr', 'de', 'ru', 'es'])) ? $form['language'] : 'en';
define('LANG', $form['language']);

//
// build character
//
$char = new \Rrs\Character();
$char->setRace($form['race']);
$char->setAge($form['age']);
$char->setDirection($form['dir']);
$char->setGabarit($form['gabarit']);
$char->setGender($form['gender']);
$char->setMorph($form['morph']);
$char->setSlot(EVisualSlot::FACE_SLOT, $form['tattoo'], $form['eyes']);
$char->setFaceShot($form['zoom'] != 'body', false);
$slots = [
    EVisualSlot::CHEST_SLOT => 'chest',
    EVisualSlot::HEAD_SLOT => 'head',
    EVisualSlot::LEGS_SLOT => 'legs',
    EVisualSlot::ARMS_SLOT => 'arms',
    EVisualSlot::HANDS_SLOT => 'hands',
    EVisualSlot::FEET_SLOT => 'feet',
    //
    EVisualSlot::RIGHT_HAND_SLOT => 'handr',
    EVisualSlot::LEFT_HAND_SLOT => 'handl',
];

foreach ($slots as $idx => $key) {
    if (isset($form[$key]['color'])) {
        $char->setSlot($idx, $form[$key]['item'], $form[$key]['color']);
    } else {
        $char->setSlot($idx, $form[$key]);
    }
}
// if no helmet is selected, then use haircut
list($hcut, $hcol) = $char->getSlot(EVisualSlot::HEAD_SLOT);
if ($hcut == 0) {
    $char->setSlot(EVisualSlot::HEAD_SLOT, $form['haircut'], $form['haircolor']);
}

// override values from user supplied vpx
if (!empty($_REQUEST['vpa'])) {
    $is_hex = (is($_REQUEST['vpax'], 'off') === 'on') && substr($_REQUEST['vpa'], 0, 2) !== '0x';
    $char->setVpa(($is_hex ? '0x' : '') . $_REQUEST['vpa']);
}
if (!empty($_REQUEST['vpb'])) {
    $is_hex = (is($_REQUEST['vpbx'], 'off') === 'on') && substr($_REQUEST['vpb'], 0, 2) !== '0x';
    $char->setVpb(($is_hex ? '0x' : '') . $_REQUEST['vpb']);
}
if (!empty($_REQUEST['vpc'])) {
    $is_hex = (is($_REQUEST['vpcx'], 'off') === 'on') && substr($_REQUEST['vpc'], 0, 2) !== '0x';
    $char->setVpc(($is_hex ? '0x' : '') . $_REQUEST['vpc']);
}

//
// ajax
//
if (is($_SERVER['HTTP_X_REQUESTED_WITH'], '') && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type; application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    $json = [
        'result' => 'success',
        'data' => [
            'image' => render_3d_url($char),
            'vpa' => '<br>(hex) '.strtoupper($char->getVpa(true)) . '<br>(dec) '.$char->getVpa(),
            'vpb' => '<br>(hex) '.strtoupper($char->getVpb(true)) . '<br>(dec) '.$char->getVpb(),
            'vpc' => '<br>(hex) '.strtoupper($char->getVpc(true)) . '<br>(dec) '.$char->getVpc(),
        ],
    ];

    echo json_encode($json, JSON_UNESCAPED_UNICODE);

    die();
}

header('Content-Type: text/html; charset=utf-8');
//header('Access-Control-Allow-Origin: *');

//
// build interface
//
$tpl2 = '<li>{$name}: <span id="{$id}">{$vpx}</span></li>';

$vpx = '<ul>';
$vpx .= strtr($tpl2, ['{$name}' => 'VPA', '{$id}' => 'vpa', '{$vpx}' => '<br>(hex) '.strtoupper($char->getVpa(true)) . '<br>(dec) '.$char->getVpa()]);
$vpx .= strtr($tpl2, ['{$name}' => 'VPB', '{$id}' => 'vpb', '{$vpx}' => '<br>(hex) '.strtoupper($char->getVpb(true)) . '<br>(dec) '.$char->getVpb()]);
$vpx .= strtr($tpl2, ['{$name}' => 'VPC', '{$id}' => 'vpc', '{$vpx}' => '<br>(hex) '.strtoupper($char->getVpc(true)) . '<br>(dec) '.$char->getVpc()]);
$vpx .= '</ul>';

//$vp = $char->getVpa(true);
//$vp = preg_replace('~(..)(?!$)\.?~', '\1:', $vp);
//echo $vp;

//************************************************************************************
$langArray = [
    'en' => __('LanguageName.uxt', 'en'),
    'fr' => __('LanguageName.uxt', 'fr'),
    'de' => __('LanguageName.uxt', 'de'),
    'ru' => __('LanguageName.uxt', 'ru'),
    'es' => __('LanguageName.uxt', 'es'),
];
$langTable = '<table><tr>';
$langTable .= '<td>' . __('uigcLanguage.uxt') . '</td>';
$langTable .= '<td>' . html_select('language', $langArray, LANG) . '</td>';
$langTable .= '</tr></table>';

$tpl = '<html>
<head>
    <title>api.bmsite.net - Character Creator</title>
    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/main.js"></script>
    <style>
        #status {
            position: absolute;
            top: 0;
            left: 0;
            padding-top: 200px;
            width: 300px;
            height: 400px; /* -padding*/
            background-color: rgba(0, 0, 0, .5);
            color: yellowgreen;
            font-size: 26px;
            text-align: center;
        }
        #form2 input[type="text"] {
            width: 200px;
        }
    </style>
</head>
<body>

<form id="form" method="POST" action="?">
    <table>
        <tr>
            <td valign="top" style="position:relative;">
                {$image}
                <br>{$vpx}<br>{$image_opts}
                <div id="status"></div>
            </td>
            <td valign="top">{$options}</td>
        </tr>
    </table>
    {$lang}
</form>

<h2>Set VPX</h2>
<form id="form2" method="POST" action="?">
    <table>
        <tr>
            <td valign="top">VPA</td>
            <td>
                <input type="text" name="vpa" value="" size="100">
            </td>
            <td>
                <input type="checkbox" name="vpax"> hex
            </td>
        </tr>
        <tr>
            <td valign="top">VPB</td>
            <td>
                <input type="text" name="vpb" value="" size="100">
            </td>
            <td>
                <input type="checkbox" name="vpbx"> hex
            </td>
        </tr>
        <tr>
            <td valign="top">VPC</td>
            <td>
                <input type="text" name="vpc" value="" size="100">
            </td>
            <td>
                <input type="checkbox" name="vpcx"> hex
            </td>
        </tr>
    </table>
    <input type="submit" name="submit" value="submit">
</form>
</body>
</html>
';

echo strtr(
    $tpl,
    [
        '{$lang}' => $langTable,
        '{$vpx}' => $vpx,
        '{$image_opts}' => image_options($char),
        //
        '{$image}' => '<img id="preview" src="' . render_3d_url($char) . '" width="300" height="600">',
        '{$options}' => option_pane($char),
        //
    ]
);

exit;

/**
 * @param \Rrs\Character $char
 *
 * @return string
 */
function render_3d_url(\Rrs\Character $char)
{
    // race, gender, age, eyes, hair, tattoo, gabarit, morph, dir, zoom [body, face, portrait]
    $race_names = [0 => 'fy', 1 => 'ma', 2 => 'tr', 3 => 'zo'];
    $race = $race_names[$char->getRace()];
    $dir = $char->getDirection();
    // age not in vpx
    //$age = $char->getAge();

    $vpa = $char->getVpa();
    $vpb = $char->getVpb();
    $vpc = $char->getVpc();

    if ($char->isFaceShot()) {
        $zoom = 'portrait';
    } else {
        $zoom = 'body';
    }

    return "http://api.bmsite.net/char/render/3d?race={$race}&dir={$dir}&zoom={$zoom}&vpa={$vpa}&vpb={$vpb}&vpc={$vpc}";
}

function image_options(\Rrs\Character $char)
{
    $tplRow = '<tr><td>{$name}</td><td>{$value}</td>';

    $zoomArray = [
        'body' => 'body',
        'portrait' => 'portrait',
    ];
    if ($char->isFaceShot()) {
        $zoom = 'portrait';
    } else {
        $zoom = 'body';
    }

    $html = '';

    //************************************************************************************
    // image options
    //************************************************************************************
    $angles = [
        0 => '0 (front)',
        45 => 45,
        90 => 90,
        135 => 135,
        180 => '180 (back)',
        225 => 225,
        270 => 270,
        315 => 315,
    ];
    $html .= strtr(
        $tplRow,
        ['{$name}' => 'Zoom', '{$value}' => html_select('zoom', $zoomArray, $zoom)]
    );

    $html .= strtr(
        $tplRow,
        [
            '{$name}' => 'Direction',
            '{$value}' => html_select('dir', $angles, (int) $char->getDirection())
        ]
    );

    return '<table>' . $html . '</table>';
}

/**
 * @param \Rrs\Character $char
 *
 * @return string
 */
function option_pane(\Rrs\Character $char)
{
    $race = strtolower(TPeople::toString($char->getRace()));
    $gender = $char->getGender() == 0 ? 'm' : 'f';

    $raceArray = [
        0 => 'Fyros',
        1 => 'Matis',
        2 => 'Tryker',
        3 => 'Zorai',
    ];
    $ageArray = [
        0 => 'age=0',
        1 => 'age=1',
        2 => 'age=2',
    ];
    $genderArray = [
        0 => 'male',
        1 => 'female',
    ];

    $tplRow = '<tr><td>{$name}</td><td>{$value}</td>';
    $sep = '<tr><td>---</td><td></td></tr>';

    $btnSubmit = strtr(
        $tplRow,
        [
            '{$name}' => '<input type="submit" name="submit" value="submit">',
            '{$value}' => ''
        ]
    );

    //************************************************************************************
    // page 1
    //************************************************************************************
    $html = '';
    $html .= strtr($tplRow, ['{$name}' => '', '{$value}' => __('uiAppear_Infos2.uxt')]);
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiR2EdRace.uxt'), '{$value}' => html_select('race', $raceArray, $char->getRace())]
    );
    // age is not in vpx
    //$html .= strtr($tplRow, ['{$name}' => 'Age', '{$value}' => html_select('age', $ageArray, $char->getAge())]);
    $html .= strtr(
        $tplRow,
        ['{$name}' => 'Gender', '{$value}' => html_select('gender', $genderArray, $char->getGender())]
    );

    $hairColors = [];
    for ($i = 0; $i < 6; $i++) {
        $hairColors[$i] = sprintf('%s_ho%s_haircolor%d.sitem', $race, $gender, $i);
    }

    $slot = EVisualSlot::HEAD_SLOT;
    list($haircut, $haircolor) = $char->getSlot($slot);
    $haircuts = [0 => '-'] + slot_items($char, $slot, 'hair');
    if (!isset($haircuts[$haircut])) {
        $haircut = 0;
    }

    $sb = '<table><tr>';
    $sb .= '<td>' . html_select("haircolor", $hairColors, $haircolor, true, false) . '</td>';
    $sb .= '<td>' . html_select("haircut", $haircuts, $haircut, true) . '</td>';
    $sb .= '</tr></table>';
    $html .= strtr(
        $tplRow,
        ['{$name}' => 'Haircut', '{$value}' => $sb]
    );

    //************************************************************************************
    $tattooArray = [];
    for ($i = 0; $i < 64; $i++) {
        $txt = sprintf('%s_ho%s_tatoo%d.sitem', $race, $gender, $i);
        $tattooArray[$i] = __($txt);
    }
    list($tattoo, $eyes) = $char->getSlot(EVisualSlot::FACE_SLOT);
    $html .= strtr(
        $tplRow,
        ['{$name}' => 'Eye color', '{$value}' => html_select('eyes', range(0, 7, 1), $eyes)]
    );
    $html .= strtr(
        $tplRow,
        ['{$name}' => 'Tattoo', '{$value}' => html_select('tattoo', $tattooArray, $tattoo)]
    );

    //************************************************************************************
    // page
    //************************************************************************************
    $html .= strtr($tplRow, ['{$name}' => '', '{$value}' => __('uiAppear_Infos2.uxt')]);
    $slots = [
        EVisualSlot::HEAD_SLOT => 'uiHelmet.uxt',
        EVisualSlot::CHEST_SLOT => 'uiTorso.uxt',
        EVisualSlot::ARMS_SLOT => 'uiArms.uxt',
        EVisualSlot::HANDS_SLOT => 'uiGloves.uxt',
        EVisualSlot::LEGS_SLOT => 'uiLegs.uxt',
        EVisualSlot::FEET_SLOT => 'uiFeet.uxt',
    ];
    $colors = [
        RyzomExtra::COLOR_RED => RyzomExtra::uxt_color(RyzomExtra::COLOR_RED) . '.uxt',
        RyzomExtra::COLOR_BEIGE => RyzomExtra::uxt_color(RyzomExtra::COLOR_BEIGE) . '.uxt',
        RyzomExtra::COLOR_GREEN => RyzomExtra::uxt_color(RyzomExtra::COLOR_GREEN) . '.uxt',
        RyzomExtra::COLOR_TURQUOISE => RyzomExtra::uxt_color(RyzomExtra::COLOR_TURQUOISE) . '.uxt',
        RyzomExtra::COLOR_BLUE => RyzomExtra::uxt_color(RyzomExtra::COLOR_BLUE) . '.uxt',
        RyzomExtra::COLOR_PURPLE => RyzomExtra::uxt_color(RyzomExtra::COLOR_PURPLE) . '.uxt',
        RyzomExtra::COLOR_WHITE => RyzomExtra::uxt_color(RyzomExtra::COLOR_WHITE) . '.uxt',
        RyzomExtra::COLOR_BLACK => RyzomExtra::uxt_color(RyzomExtra::COLOR_BLACK) . '.uxt',
    ];

    // FIXME: split helmet and haircut - different colors
    foreach ($slots as $slot => $txt) {
        list($item, $color) = $char->getSlot($slot);

        $items = [0 => '-'] + slot_items($char, $slot, 'item');

        $sb = '<table><tr>';
        $sb .= '<td>' . html_select("slot{$slot}[color]", $colors, $color, true, false) . '</td>';
        $sb .= '<td>' . html_select("slot{$slot}[item]", $items, $item, true) . '</td>';
        $sb .= '</tr></table>';

        $html .= strtr($tplRow, ['{$name}' => __($txt), '{$value}' => $sb]);
    }
    //***********************************************************************************
    list($item) = $char->getSlot(EVisualSlot::RIGHT_HAND_SLOT);
    $items = [0 => '-'] + slot_items($char, EVisualSlot::RIGHT_HAND_SLOT);
    $html .= strtr(
        $tplRow,
        [
            '{$name}' => 'Right hand',
            '{$value}' => html_select(
                'slot' . EVisualSlot::RIGHT_HAND_SLOT,
                $items,
                $item,
                true
            )
        ]
    );
    list($item) = $char->getSlot(EVisualSlot::LEFT_HAND_SLOT);
    $items = [0 => '-'] + slot_items($char, EVisualSlot::LEFT_HAND_SLOT);
    $html .= strtr(
        $tplRow,
        [
            '{$name}' => 'Left hand',
            '{$value}' => html_select(
                'slot' . EVisualSlot::LEFT_HAND_SLOT,
                $items,
                $item,
                true
            )
        ]
    );

    $html .= $btnSubmit;

    //************************************************************************************
    // page
    //************************************************************************************
    $tmp = '<table>';
    $tmp .= '<tr><td>' . __('uiAppear_Infos3.uxt') . '</td><td>' . __('uiAppear_Infos4.uxt') . '</td></tr>';
    $tmp .= '<tr><td valign="top">' . render_gabarit($char) . '</td><td valign="top">' . render_morph(
            $char
        ) . '</td></tr>';
    $tmp .= '</table>';
    $html .= strtr($tplRow, ['{$name}' => '', '{$value}' => $tmp]);
    //$html .= strtr($tplRow, ['{$name}' => '', '{$value}' => __('uiAppear_Infos3.uxt')]);
    //$html .= strtr($tplRow, ['{$name}' => '', '{$value}' => render_gabarit($char)]);
    //************************************************************************************
    //$html .= strtr($tplRow, ['{$name}' => '', '{$value}' => __('uiAppear_Infos4.uxt')]);
    //$html .= strtr($tplRow, ['{$name}' => '', '{$value}' => render_morph($char)]);

    $html .= $btnSubmit;

    return '<table>' . $html . '</table>';
}

/**
 * @param \Rrs\Character $char
 *
 * @return string
 */
function render_gabarit(\Rrs\Character $char)
{
    $tplRow = '<tr><td>{$name}</td><td>{$value}</td></tr>';
    $gabarit = $char->getGabarit();
    $html = '';
    $html .= '<table>';
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiHeight.uxt'), '{$value}' => html_select('gabarit[0]', range(0, 14, 1), $gabarit[0])]
    );
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiTorso.uxt'), '{$value}' => html_select('gabarit[1]', range(0, 14, 1), $gabarit[1])]
    );
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiArms.uxt'), '{$value}' => html_select('gabarit[2]', range(0, 14, 1), $gabarit[2])]
    );
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiLegs.uxt'), '{$value}' => html_select('gabarit[3]', range(0, 14, 1), $gabarit[3])]
    );
    $html .= strtr(
        $tplRow,
        ['{$name}' => __('uiBreasts.uxt'), '{$value}' => html_select('gabarit[4]', range(0, 14, 1), $gabarit[4])]
    );
    $html .= '</table>';
    return $html;
}

/**
 * @param \Rrs\Character $char
 *
 * @return string
 */
function render_morph(\Rrs\Character $char)
{
    $race = TPeople::toString($char->getRace());
    $tplRow = '<tr><td>{$name}</td><td>{$value}</td></tr>';

    // uiFace1Fy
    $uiMorph = 'uiFace%d' . ucfirst($race) . '.uxt';
    $morph = $char->getMorph();
    $html = '<table>';
    for ($i = 0; $i < 8; $i++) {
        $html .= strtr(
            $tplRow,
            [
                '{$name}' => __(sprintf($uiMorph, $i + 1)),
                '{$value}' => html_select("morph[{$i}]", range(0, 7, 1), $morph[$i])
            ]
        );
    }
    $html .= '</table>';
    return $html;
}


/**
 * @param string     $name
 * @param array      $options
 * @param int|string $selected
 * @param bool       $trans
 * @param bool       $verbose
 *
 * @return string
 */
function html_select($name, array $options, $selected, $trans = false, $verbose = true)
{
    $ret = '<select name="' . $name . '">';
    foreach ($options as $k => $v) {
        if ($v === '-') {
            $txt = '-';
        } else {
            $txt = ($trans ? __($v) : _h($v));
            if (substr($txt, 0, 10) == 'NotFound:(') {
                $txt = _h($v);
            } elseif ($verbose && $txt != $v) {
                $txt = '[' . _h($v) . '] ' . $txt;
            }
        }
        $ret .= '<option value="' . _h($k) . '"' . ($selected == $k ? ' selected="selected"' : '') . '>' .
            $txt . '</option>';
    }
    $ret .= '</select>';
    //$ret = '['.$selected.']'.$ret;
    return $ret;
}

/**
 * FIXME: filter items for race and gender?
 *
 * @param \Rrs\Character $char
 * @param int            $index
 * @param string         $what [all, hair, item]
 *
 * @return mixed
 */
function slot_items(\Rrs\Character $char, $index, $what = 'all')
{
    static $visual = null;

    if ($visual === null) {
        $visual = ryzom_extra_load_vs();
    }

    if ($what !== 'all' && $index == EVisualSlot::HEAD_SLOT) {
        $ret = [];
        $race = strtolower(TPeople::toString($char->getRace()));
        $gender = strtolower(EGender::toString($char->getGender()));
        foreach ($visual[$index] as $index => $sheet) {
            // FIXME: fy_hof_haircolor0.sitem ... color5
            if (is_haircut($sheet)) {
                if ($what == 'hair') {
                    // 'fy_hom_'
                    $prefix = sprintf('%s_ho%s_', $race, $gender);
                    // 'fy_cheveux'
                    $prefix2 = sprintf('%s_cheveux', $race);
                    $pos = stripos($sheet, $prefix);
                    $pos2 = stripos($sheet, $prefix2);
                    if ($pos === 0 || $pos2 === 0) {
                        $ret[$index] = $sheet;
                    }
                }
            } elseif ($what == 'item') {
                $ret[$index] = $sheet;
            }
        }
        //$items = filter_haircut($char, $slot);
        return $ret;
    }

    return $visual[$index];
}

/**
 * @param string $sheet
 *
 * @return bool
 */
function is_haircut($sheet)
{
    return strstr($sheet, '_hair_') || strstr($sheet, '_cheveux');
}

/**
 * @param int $var
 * @param int $min
 * @param int $max
 *
 * @return mixed
 */
function clamp($var, $min, $max)
{
    if ($var < $min) {
        return $min;
    }
    if ($var > $max) {
        return $max;
    }
    return $var;
}

/**
 * @param mixed $var
 * @param mixed $default
 *
 * @return mixed
 */
function is(&$var, $default = null)
{
    return (isset($var) ? $var : $default);
}
