<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Object;

/**
 * UrlRule 代表 UrlManager用于解析和生成url的规则。
 * UrlRule represents a rule used by [[UrlManager]] for parsing and generating URLs.
 *
 * To define your own URL parsing and creation logic you can extend from this class
 * and add it to [[UrlManager::rules]] like this:
 *
 * ```php
 * 'rules' => [
 *     ['class' => 'MyUrlRule', 'pattern' => '...', 'route' => 'site/index', ...],
 *     // ...
 * ]
 * ```
 *
rules数组 示例：
其中键key 相当于请求，类似于正则，用来匹配请求   可称为请求 pattern  
值value 表示要解析到的路径    可称为路由 route

 'rules' => [
    // 为路由指定了一个别名，以 post 的复数形式来表示 post/index 路由
    'posts' => 'post/index',

    // id 是命名参数，post/100 形式的URL，其实是 post/view&id=100
    'post/<id:\d+>' => 'post/view',

    // controller action 和 id 以命名参数形式出现
    '<controller:(post|comment)>/<id:\d+>/<action:(create|update|delete)>'
        => '<controller>/<action>',

    // 包含了 HTTP 方法限定，仅限于DELETE方法
    'DELETE <controller:\w+>/<id:\d+>' => '<controller>/delete',

    // 需要将 Web Server 配置成可以接收 *.digpage.com 域名的请求
    'http://<user:\w+>.digpage.com/<lang:\w+>/profile' => 'user/profile',
]
 * 
 * @property null|int $createUrlStatus Status of the URL creation after the last [[createUrl()]] call. `null`
 * if rule does not provide info about create status. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UrlRule extends Object implements UrlRuleInterface
{
    /**
     * 只解析
     * Set [[mode]] with this value to mark that this rule is for URL parsing only
     */
    const PARSING_ONLY = 1;
    /**
     * 只创建
     * Set [[mode]] with this value to mark that this rule is for URL creation only
     */
    const CREATION_ONLY = 2;
    /**
     * Represents the successful URL generation by last [[createUrl()]] call.
     * @see $createStatus
     * @since 2.0.12
     */
    const CREATE_STATUS_SUCCESS = 0;
    /**
     * Represents the unsuccessful URL generation by last [[createUrl()]] call, because rule does not support
     * creating URLs.
     * @see $createStatus
     * @since 2.0.12
     */
    const CREATE_STATUS_PARSING_ONLY = 1;
    /**
     * Represents the unsuccessful URL generation by last [[createUrl()]] call, because of mismatched route.
     * @see $createStatus
     * @since 2.0.12
     */
    const CREATE_STATUS_ROUTE_MISMATCH = 2;
    /**
     * Represents the unsuccessful URL generation by last [[createUrl()]] call, because of mismatched
     * or missing parameters.
     * @see $createStatus
     * @since 2.0.12
     */
    const CREATE_STATUS_PARAMS_MISMATCH = 4;

    /**
     * 路由规则名称
     * 
     * @var string the name of this rule. If not set, it will use [[pattern]] as the name.
     */
    public $name;
    /**
     * 用于解析请求或生成URL的模式，通常是正则表达式
     * On the rule initialization, the [[pattern]] matching parameters names will be replaced with [[placeholders]].
     * @var string the pattern used to parse and create the path info part of a URL.
     * @see host
     * @see placeholders
     */
    public $pattern;
    /**
     * 用于解析或创建URL时，处理主机信息的部分，如 http://www.digpage.com
     * @var string the pattern used to parse and create the host info part of a URL (e.g. `http://example.com`).
     * @see pattern
     */
    public $host;
    /**
     * 指向controller 和 action 的路由
     * @var string the route to the controller action
     */
    public $route;
    /**
     * 以一组键值对数组指定若干GET参数，在当前规则用于解析请求时，
       这些GET参数会被注入到 $_GET 中去
     * @var array the default GET parameters (name => value) that this rule provides.
     * When this rule is used to parse the incoming request, the values declared in this property
     * will be injected into $_GET.
     */
    public $defaults = [];
    /**
     * 指定URL的后缀，通常是诸如 ".html" 等，
       使得一个URL看起来好像指向一个静态页面。
       如果这个值未设定，使用 UrlManager::suffix 的值。
     * @var string the URL suffix used for this rule.
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page.
     * If not set, the value of [[UrlManager::suffix]] will be used.
     */
    public $suffix;
    /**
     * 指定当前规则适用的HTTP方法，如 GET, POST, DELETE 等。
      可以使用数组表示同时适用于多个方法。
      如果未设定，表明当前规则适用于所有方法。
      当然，这个属性仅在解析请求时有效，在生成URL时是无效的。
     * @var string|array the HTTP verb (e.g. GET, POST, DELETE) that this rule should match.
     * Use array to represent multiple verbs that this rule may match.
     * If this property is not set, the rule can match any verb.
     * Note that this property is only used when parsing a request. It is ignored for URL creation.
     */
    public $verb;
    /**
     * 表明当前规则的工作模式，取值可以是 0, PARSING_ONLY, CREATION_ONLY。
        未设定时等同于0。
     * @var int a value indicating if this rule should be used for both request parsing and URL creation,
     * parsing only, or creation only.
     * If not set or 0, it means the rule is both request parsing and URL creation.
     * If it is [[PARSING_ONLY]], the rule is for request parsing only.
     * If it is [[CREATION_ONLY]], the rule is for URL creation only.
     */
    public $mode;
    /**
     * 表明URL中的参数是否需要进行url编码，默认是进行。
     * @var bool a value indicating if parameters should be url encoded.
     */
    public $encodeParams = true;
    /**
     * 定义标准化
     * @var UrlNormalizer|array|false|null the configuration for [[UrlNormalizer]] used by this rule.
     * If `null`, [[UrlManager::normalizer]] will be used, if `false`, normalization will be skipped
     * for this rule.
     * @since 2.0.10
     */
    public $normalizer;

    /**
     * @var int|null status of the URL creation after the last [[createUrl()]] call.
     * @since 2.0.12
     */
    protected $createStatus;
    /**
     * 占位符
     * @var array list of placeholders for matching parameters names. Used in [[parseRequest()]], [[createUrl()]].
     * On the rule initialization, the [[pattern]] parameters names will be replaced with placeholders.
     * This array contains relations between the original parameters names and their placeholders.
     * The array keys are the placeholders and the values are the original names.
     *
     * @see parseRequest()
     * @see createUrl()
     * @since 2.0.7
     */
    protected $placeholders = [];

    /**
     * 用于生成新URL的模板
     * @var string the template for generating a new URL. This is derived from [[pattern]] and is used in generating URL.
     */
    private $_template;
    /**
     * 一个用于匹配路由部分的正则表达式，用于生成URL
     * @var string the regex for matching the route part. This is used in generating URL.
     */
    private $_routeRule;
    /**
     * 用于保存一组匹配参数的正则表达式，用于生成URL
     * @var array list of regex for matching parameters. This is used in generating URL.
     */
    private $_paramRules = [];
    /**
     * 保存一组路由中使用的参数
     * @var array list of parameters used in the route.
     */
    private $_routeParams = [];


    /**
     * 把对象当成字符串使用的时候调用
     * @return string
     * @since 2.0.11
     */
    public function __toString()
    {
        $str = '';
        // 适用的请求方法
        if ($this->verb !== null) {
            $str .= implode(',', $this->verb) . ' ';
        }
        // 没在路由规则name中匹配到host则添加host
        if ($this->host !== null && strrpos($this->name, $this->host) === false) {
            $str .= $this->host . '/';
        }
        $str .= $this->name;

        if ($str === '') {
            return '/';
        }
        return $str;
    }

    /**
     * Initializes this rule.
     */
    public function init()
    {
        // 一个路由规则必定要有 请求部分pattern ，否则无法解析是没有意义的，
        // 就是匹配请求的部分
        if ($this->pattern === null) {
            throw new InvalidConfigException('UrlRule::pattern must be set.');
        }
        // 不指定规则匹配后所要指派的路由，Yii怎么知道将请求交给谁来处理？
        // 就是将匹配到的解析到……
        if ($this->route === null) {
            throw new InvalidConfigException('UrlRule::route must be set.');
        }
        // 初始化 $this->normalizer
        // 标准化对象
        if (is_array($this->normalizer)) {
            $normalizerConfig = array_merge(['class' => UrlNormalizer::className()], $this->normalizer);
            $this->normalizer = Yii::createObject($normalizerConfig);
        }
        if ($this->normalizer !== null && $this->normalizer !== false && !$this->normalizer instanceof UrlNormalizer) {
            throw new InvalidConfigException('Invalid config for UrlRule::normalizer.');
        }
        // 允许的请求方法 转大写
        if ($this->verb !== null) {
            if (is_array($this->verb)) {
                foreach ($this->verb as $i => $verb) {
                    $this->verb[$i] = strtoupper($verb);
                }
            } else {
                $this->verb = [strtoupper($this->verb)];
            }
        }
        // 路由规则名称
        if ($this->name === null) {
            $this->name = $this->pattern;
        }
        // 预处理请求部分
        $this->preparePattern();
    }

    /**
     * 初始化时处理请求部分
     * Process [[$pattern]] on rule initialization.
     */
    private function preparePattern()
    {
        // trim两边的 /
        $this->pattern = $this->trimSlashes($this->pattern);
        $this->route = trim($this->route, '/');

        // 设置了 host 域名则拼接 pattern
        if ($this->host !== null) {
            $this->host = rtrim($this->host, '/');
            $this->pattern = rtrim($this->host . '/' . $this->pattern, '/');
        //  pattern 为空直接 return
        // 既未定义 host ，pattern 又是空的，那么 pattern 匹配任意字符串。
        // 而基于这个pattern的，用于生成的URL的template就是空的，
        // 意味着使用该规则生成所有URL都是空的。
        // 后续也无需再作其他初始化工作了。
        } elseif ($this->pattern === '') {
            // 生成url模板为空
            $this->_template = '';
            // 正则懒惰匹配，# 为分界符 u为懒惰模式
            $this->pattern = '#^$#u';

            return;
        // 获取路由规则请求部分定义的域名
        // pattern中存在 :// 如http://www.bunao.me   获取域名
        } elseif (($pos = strpos($this->pattern, '://')) !== false) {
            // 处理 :// 后还包含 /
            if (($pos2 = strpos($this->pattern, '/', $pos + 3)) !== false) {
                // 域名
                $this->host = substr($this->pattern, 0, $pos2);
            } else {
                $this->host = $this->pattern;
            }
        // 获取路由规则请求部分定义的域名
        // 以 // 开头  如 //www.bunao.me   获取域名
        } elseif (strpos($this->pattern, '//') === 0) {
            // 除了 // 还有 /
            if (($pos2 = strpos($this->pattern, '/', 2)) !== false) {
                $this->host = substr($this->pattern, 0, $pos2);
            } else {
                $this->host = $this->pattern;
            }
        // pattern 不是空串，且不包含主机信息，两端加上 '/' ，形成一个正则
        } else {
            $this->pattern = '/' . $this->pattern . '/';
        }
        // route 中含有 <参数> ,如下面的规则
        // ['<controller:(test|comment)>/<id:\d+>/<action:(bunao|update|delete)>' => '<controller>/<action>']
        // 则将所有参数提取成 [参数 => <参数>]
        // 如 ['controller' => '<controller>', 'action' => '<action>'],
        // 获取 route 参数
        if (strpos($this->route, '<') !== false && preg_match_all('/<([\w._-]+)>/', $this->route, $matches)) {
            foreach ($matches[1] as $name) {
                $this->_routeParams[$name] = "<$name>";
            }
        }
        // 转化请求patten部分
        $this->translatePattern(true);
    }

    /**
     * 转换
     * Prepares [[$pattern]] on rule initialization - replace parameter names by placeholders.
     *
     * @param bool $allowAppendSlash Defines position of slash in the param pattern in [[$pattern]]. 允许有斜线/
     * If `false` slash will be placed at the beginning of param pattern. If `true` slash position will be detected
     * depending on non-optional pattern part.
     */
    private function translatePattern($allowAppendSlash)
    {
        // 这个 $tr[] 和 $tr2[] 用于字符串的转换
        $tr = [
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ];

        $tr2 = [];
        $requiredPatternPart = $this->pattern;
        $oldOffset = 0;
        // pattern 中含有 <参数名:参数pattern> ，
        // 其中 ':参数pattern' 部分是可选的。
/**
如  '/post/<ding:\w>/<id:\d+>/<ran>' 
结果 ： json_encode($matches)
[
    [
        [
            "<ding:\\w>",
            6
        ],
        [
            "ding",
            7
        ],
        [
            "\\w",
            12
        ]
    ],
    [
        [
            "<id:\\d+>",
            16
        ],
        [
            "id",
            17
        ],
        [
            "\\d+",
            20
        ]
    ],
    [
        [
            "<ran>",
            25
        ],
        [
            "ran",
            26
        ]
    ]
]
 */
        if (preg_match_all('/<([\w._-]+):?([^>]+)?>/', $this->pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $appendSlash = false;
            foreach ($matches as $match) {
                //([\w._-]+)匹配到的
                /*
                [
                    "ding",
                    7
                ],
                 */
                $name = $match[1][0];
                //([^>]+)匹配到的
                //如果没有匹配到则 使用[^\/]+ 表示匹配除了 '/' 以外的所有字符
                /*
                [
                    "\\w",
                    12
                ]
                 */
                $pattern = isset($match[2][0]) ? $match[2][0] : '[^\/]+';
                // 占位符
                $placeholder = 'a' . hash('crc32b', $name); // placeholder must begin with a letter
                $this->placeholders[$placeholder] = $name;
                // 如果 defaults[] 中有同名参数，如下面定义的这条规则
                /*
                [
                    'pattern' => '/post/<ding:\w>/<id:\d+>/<ran>',// 请求部分
                    'route' => 'post/index',// 解析部分
                    'defaults' => ['ding' => 'bunao', 'id' => 1],// 默认值
                ],
                 */
                if (array_key_exists($name, $this->defaults)) {
                    // 匹配到整体的长度
                    /*
                    [
                        "<ding:\\w>",
                        6
                    ]
                     */
                    $length = strlen($match[0][0]);
                    // 开始位置
                    $offset = $match[0][1];
                    // 将 $requiredPatternPart /post/<ding:\w>/<id:\d+>/<ran> 中的 /{$match[0][0]}/ 替换成 //
                    // /post////<id:\d+>/<ran>
                    $requiredPatternPart = str_replace("/{$match[0][0]}/", '//', $requiredPatternPart);
                    // 从第一个开始的匹配到的
                    if (
                        //允许开头有斜线 /
                        $allowAppendSlash  
                        // 第一个且pattern是以 / 开始的  如'/<bunao:\d>/<ding:\w>/<id:\d+>/<ran>' 中的 bunao
                        && ($appendSlash || $offset === 1)
                        && (($offset - $oldOffset) === 1)
                        // 判断是否有 后/
                        && isset($this->pattern[$offset + $length])
                        && $this->pattern[$offset + $length] === '/'
                        // 后/ 还有内容
                        && isset($this->pattern[$offset + $length + 1])
                    ) {
                        // if pattern starts from optional params, put slash at the end of param pattern
                        // @see https://github.com/yiisoft/yii2/issues/13086
                        $appendSlash = true;
                        // ?P<$placeholder> 分组其别名的格式，如python的一个例子
                        // re.match(r"<(?P<name1>\w*)><(?P<name2>\w*)>.*</(?P=name2)></(?P=name1)>", "<html><h1>www.itcast.cn</h1></html>")
                        // $tr["<ding>/"] = "((?P<$placeholder>\\w)/)?";
                        // 如果有就是第一个 bunao 匹配到了，之后的才有可能以这种形式存
                        $tr["<$name>/"] = "((?P<$placeholder>$pattern)/)?";
                    // 从中间开始匹配到的
                    } elseif (
                        // 中间的
                        $offset > 1
                        && $this->pattern[$offset - 1] === '/'
                        && (!isset($this->pattern[$offset + $length]) || $this->pattern[$offset + $length] === '/')
                    ) {
                        $appendSlash = false;
                        // 中间的 ding  id
                        $tr["/<$name>"] = "(/(?P<$placeholder>$pattern))?";
                    }
                    // 所有匹配到的
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)?";
                    $oldOffset = $offset + $length;
                } else {
                    $appendSlash = false;
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)";
                }
                // 如果路由中也有name
                if (isset($this->_routeParams[$name])) {
                    $tr2["<$name>"] = "(?P<$placeholder>$pattern)";
                } else {
                    $this->_paramRules[$name] = $pattern === '[^\/]+' ? '' : "#^$pattern$#u";
                }
            }
        }
        // 如果全部为 ////
        // we have only optional params in route - ensure slash position on param patterns
        if ($allowAppendSlash && trim($requiredPatternPart, '/') === '') {
            $this->translatePattern(false);
            return;
        }
        // 将 pattern 中所有的 <参数名:参数pattern> 替换成 <参数名> 后作为 _template 如
        // /<bunao:\d>/<ding:\w>/<id:\d+>/<ran> 转换成下面的
        // /<bunao>/<ding>/<id>/<ran>
        $this->_template = preg_replace('/<([\w._-]+):?([^>]+)?>/', '<$1>', $this->pattern);
        // 将 _template 中的特殊字符及字符串使用 tr[] 进行转换，并作为最终的pattern
        $this->pattern = '#^' . trim(strtr($this->_template, $tr), '/') . '$#u';

        // if host starts with relative scheme, then insert pattern to match any
        if (strpos($this->host, '//') === 0) {
            $this->pattern = substr_replace($this->pattern, '[\w]+://', 2, 0);
        }
        // 如果指定了 routePrams 还要使用 tr2[] 对 route 进行转换，
        // 并作为最终的 _routeRule
        if (!empty($this->_routeParams)) {
            $this->_routeRule = '#^' . strtr($this->route, $tr2) . '$#u';
        }
    }

    /**
     * @param UrlManager $manager the URL manager
     * @return UrlNormalizer|null
     * @since 2.0.10
     */
    protected function getNormalizer($manager)
    {
        if ($this->normalizer === null) {
            return $manager->normalizer;
        } else {
            return $this->normalizer;
        }
    }

    /**
     * @param UrlManager $manager the URL manager
     * @return bool
     * @since 2.0.10
     */
    protected function hasNormalizer($manager)
    {
        return $this->getNormalizer($manager) instanceof UrlNormalizer;
    }

    /**
     * 解析请求
     * Parses the given request and returns the corresponding route and parameters.
     * @param UrlManager $manager the URL manager uslManager对象
     * @param Request $request the request component Request对象
     * @return array|bool the parsing result. The route and the parameters are returned as an array.
     * If `false`, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        // 当前路由规则仅限于创建URL，直接返回 false。
        // 该方法返回false表示当前规则不适用于当前的URL。
        if ($this->mode === self::CREATION_ONLY) {
            return false;
        }
        // 检查请求方法是否符合定义的
        if (!empty($this->verb) && !in_array($request->getMethod(), $this->verb, true)) {
            return false;
        }
        // 后缀
        $suffix = (string)($this->suffix === null ? $manager->suffix : $this->suffix);
        // 获取URL中入口脚本之后、查询参数 ? 号之前的全部内容
        $pathInfo = $request->getPathInfo();
        $normalized = false;
        // 如果定义的Normalizer，对pathinfo进行规范化，没用过
        if ($this->hasNormalizer($manager)) {
            $pathInfo = $this->getNormalizer($manager)->normalizePathInfo($pathInfo, $suffix, $normalized);
        }
        // 有假后缀且有PATH_INFO
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            // 当前请求的 PATH_INFO 以该假后缀结尾，留意 -$n 的用法
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    // suffix alone is not allowed
                    return false;
                }
            } else {
                return false;
            }
        }
        // 规则定义了主机信息，即 http://www.digpage.com 之类，那要把主机信息接回去。
        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }
        // 当前URL是否匹配规则，留意这个pattern是经过 init() 转换的
        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }
        $matches = $this->substitutePlaceholderNames($matches);

        foreach ($this->defaults as $name => $value) {
            if (!isset($matches[$name]) || $matches[$name] === '') {
                $matches[$name] = $value;
            }
        }
        $params = $this->defaults;
        $tr = [];
        foreach ($matches as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                $tr[$this->_routeParams[$name]] = $value;
                unset($params[$name]);
            } elseif (isset($this->_paramRules[$name])) {
                $params[$name] = $value;
            }
        }
        if ($this->_routeRule !== null) {
            $route = strtr($this->route, $tr);
        } else {
            $route = $this->route;
        }

        Yii::trace("Request parsed with URL rule: {$this->name}", __METHOD__);

        if ($normalized) {
            // pathInfo was changed by normalizer - we need also normalize route
            return $this->getNormalizer($manager)->normalizeRoute([$route, $params]);
        } else {
            return [$route, $params];
        }
    }

    /**
     * Creates a URL according to the given route and parameters.
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     * @return string|bool the created URL, or `false` if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {
        if ($this->mode === self::PARSING_ONLY) {
            $this->createStatus = self::CREATE_STATUS_PARSING_ONLY;
            return false;
        }

        $tr = [];

        // match the route part first
        if ($route !== $this->route) {
            if ($this->_routeRule !== null && preg_match($this->_routeRule, $route, $matches)) {
                $matches = $this->substitutePlaceholderNames($matches);
                foreach ($this->_routeParams as $name => $token) {
                    if (isset($this->defaults[$name]) && strcmp($this->defaults[$name], $matches[$name]) === 0) {
                        $tr[$token] = '';
                    } else {
                        $tr[$token] = $matches[$name];
                    }
                }
            } else {
                $this->createStatus = self::CREATE_STATUS_ROUTE_MISMATCH;
                return false;
            }
        }

        // match default params
        // if a default param is not in the route pattern, its value must also be matched
        foreach ($this->defaults as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                continue;
            }
            if (!isset($params[$name])) {
                // allow omit empty optional params
                // @see https://github.com/yiisoft/yii2/issues/10970
                if (in_array($name, $this->placeholders) && strcmp($value, '') === 0) {
                    $params[$name] = '';
                } else {
                    $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                    return false;
                }
            }
            if (strcmp($params[$name], $value) === 0) { // strcmp will do string conversion automatically
                unset($params[$name]);
                if (isset($this->_paramRules[$name])) {
                    $tr["<$name>"] = '';
                }
            } elseif (!isset($this->_paramRules[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        // match params in the pattern
        foreach ($this->_paramRules as $name => $rule) {
            if (isset($params[$name]) && !is_array($params[$name]) && ($rule === '' || preg_match($rule, $params[$name]))) {
                $tr["<$name>"] = $this->encodeParams ? urlencode($params[$name]) : $params[$name];
                unset($params[$name]);
            } elseif (!isset($this->defaults[$name]) || isset($params[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        $url = $this->trimSlashes(strtr($this->_template, $tr));
        if ($this->host !== null) {
            $pos = strpos($url, '/', 8);
            if ($pos !== false) {
                $url = substr($url, 0, $pos) . preg_replace('#/+#', '/', substr($url, $pos));
            }
        } elseif (strpos($url, '//') !== false) {
            $url = preg_replace('#/+#', '/', trim($url, '/'));
        }

        if ($url !== '') {
            $url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
        }

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }

        $this->createStatus = self::CREATE_STATUS_SUCCESS;
        return $url;
    }

    /**
     * Returns status of the URL creation after the last [[createUrl()]] call.
     *
     * @return null|int Status of the URL creation after the last [[createUrl()]] call. `null` if rule does not provide
     * info about create status.
     * @see $createStatus
     * @since 2.0.12
     */
    public function getCreateUrlStatus() {
        return $this->createStatus;
    }

    /**
     * Returns list of regex for matching parameter.
     * @return array parameter keys and regexp rules.
     *
     * @since 2.0.6
     */
    protected function getParamRules()
    {
        return $this->_paramRules;
    }

    /**
     * Iterates over [[placeholders]] and checks whether each placeholder exists as a key in $matches array.
     * When found - replaces this placeholder key with a appropriate name of matching parameter.
     * Used in [[parseRequest()]], [[createUrl()]].
     *
     * @param array $matches result of `preg_match()` call
     * @return array input array with replaced placeholder keys
     * @see placeholders
     * @since 2.0.7
     */
    protected function substitutePlaceholderNames(array $matches)
    {
        foreach ($this->placeholders as $placeholder => $name) {
            if (isset($matches[$placeholder])) {
                $matches[$name] = $matches[$placeholder];
                unset($matches[$placeholder]);
            }
        }
        return $matches;
    }

    /**
     * 去除字符串的两边的 /。如果字符串以'//'开头，则会留下两个斜杠
在字符串的开头。
     * Trim slashes in passed string. If string begins with '//', two slashes are left as is
     * in the beginning of a string.
     *
     * @param string $string
     * @return string
     */
    private function trimSlashes($string)
    {
        if (strpos($string, '//') === 0) {
            return '//' . trim($string, '/');
        }
        return trim($string, '/');
    }
}
