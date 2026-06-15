# WordPress 产品分类导航与动态筛选主题

> 一套专注研究 **WordPress 产品筛选系统** 的经典主题示例，完整演示产品分类、动态属性、`tax_query`、`meta_query`、AJAX、联动计数、缓存、分页、URL 状态，以及电脑端和手机端筛选体验。

![Version](https://img.shields.io/badge/version-v1.7.0-2563eb)
![Status](https://img.shields.io/badge/status-stable-16a34a)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

![项目预览](screenshot.png)

---

## 项目简介

本项目最初用于研究 WordPress 产品分类导航和多条件筛选，经过多个版本迭代，已经形成一套完整的产品筛选学习主题。

项目重点不是商城、会员或订单系统，而是研究以下核心问题：

```text
产品数据如何组织
↓
筛选条件如何配置
↓
URL 参数如何验证
↓
tax_query 与 meta_query 如何生成
↓
普通 GET 与 AJAX 如何共用查询逻辑
↓
筛选项数量如何联动计算
↓
结果如何缓存并自动失效
↓
电脑端和手机端如何获得良好体验
```

当前稳定版本：

```text
v1.7.0
```

---

## 项目定位

本项目以后只维护与“产品筛选”直接相关的内容：

- 产品分类与属性设计；
- 筛选 Schema；
- 参数清理与白名单验证；
- taxonomy 与 post meta 查询；
- AJAX 无刷新筛选；
- 联动筛选数量；
- 零结果选项禁用；
- 缓存与自动失效；
- 排序、分页与 URL 状态；
- 电脑端筛选面板；
- 手机端筛选抽屉；
- 筛选代码注释与学习文档。

本项目明确不扩展：

- 产品收藏；
- 产品对比；
- 购物车；
- 订单和支付；
- 会员中心；
- 推荐系统；
- 大型前端框架。

---

# 核心功能

## 产品数据系统

- `product` 自定义文章类型；
- 产品层级分类 `product_category`；
- 品牌、电压、功能、材质等属性分类法；
- 价格、功率、转速、宽度等数值字段；
- 产品详情页；
- 产品归档页；
- 产品分类页；
- 后台产品列表增强；
- 演示产品与属性数据导入。

## 多层级产品分类导航

- 显示当前分类的完整祖先路径；
- 显示当前层级的兄弟分类；
- 显示当前分类的下一层子分类；
- 当前分类高亮；
- 祖先分类路径高亮；
- 支持返回上一级分类；
- 一次读取分类并建立父子索引，避免重复查询。

## 动态产品筛选

- taxonomy 多选筛选；
- taxonomy 的 `IN` 和 `AND`；
- post meta 数值范围筛选；
- 分类专属筛选方案；
- 子分类继承父分类筛选方案；
- 筛选组后台配置；
- 自动隐藏当前分类没有数据的筛选组；
- 已选条件标签；
- 单项清除、分组清除、全部清除；
- 排序；
- 分页。

## AJAX 与渐进增强

- 条件变化后自动更新产品；
- AJAX 更新产品数量；
- AJAX 更新分页；
- AJAX 更新联动筛选数量；
- 使用 `AbortController` 取消旧请求；
- 使用防抖减少重复请求；
- 请求失败后支持手动重试；
- 请求失败后可回退到普通 GET 页面；
- JavaScript 不可用时，普通表单仍可使用。

## 联动筛选计数

- 每个筛选项显示候选产品数量；
- 计算某一组时排除该组自身条件；
- `IN` 组按“选择该项后”的数量计算；
- `AND` 组按“追加该项后”的数量计算；
- 数值区间显示切换后的候选数量；
- 数量为 0 的未选项自动禁用；
- 已选择的零结果项仍可取消。

## 缓存与性能

- 使用 WordPress Transient 缓存 Facet 结果；
- 缓存键包含分类、筛选方案和筛选状态；
- 产品或属性变化后自动提升缓存版本；
- 旧缓存自然过期，避免批量扫描删除；
- 管理员可开启筛选性能调试；
- 显示实时计算或缓存命中耗时。

## 电脑端体验

- 稳定的标题列与内容列布局；
- 中文标题不逐字换行；
- 筛选组可折叠；
- 收起状态只保留一行；
- 选项过多时支持“显示更多”；
- 大量选项支持组内搜索；
- 已选项自动置顶；
- 零结果项排在末尾；
- 已选标签区域限制高度；
- 吸顶筛选摘要；
- 骨架加载状态。

## 手机端体验

- 右侧筛选抽屉；
- `100dvh` 动态视口；
- 固定抽屉头部；
- 固定底部操作栏；
- 手机安全区域适配；
- 触控区域不低于 44px；
- 遮罩关闭；
- `Esc` 关闭；
- 焦点循环；
- 关闭后焦点返回；
- 背景滚动锁定；
- 实时显示产品数量。

---

# 技术栈

| 类型 | 技术 |
|---|---|
| CMS | WordPress |
| 主题类型 | Classic Theme |
| 后端 | PHP |
| 数据查询 | `WP_Query`、`tax_query`、`meta_query` |
| 前端 | HTML、CSS、原生 JavaScript |
| AJAX | WordPress `admin-ajax.php` |
| 状态管理 | URLSearchParams、History API |
| 缓存 | WordPress Transient |
| 数据安全 | Nonce、白名单、sanitize 系列函数 |
| 响应式 | CSS Media Queries、`100dvh` |
| 无障碍 | ARIA、焦点管理、键盘操作 |

---

# 数据模型

## 1. 产品文章类型

```text
product
```

由 `register_post_type()` 注册。

主要承载：

- 产品标题；
- 产品正文；
- 产品摘要；
- 产品特色图片；
- 产品详情页；
- 产品归档页。

## 2. 产品分类

```text
product_category
```

这是一个层级 taxonomy，用于表示：

```text
工业设备
└── 包装设备
    ├── 真空包装机
    ├── 封口机
    └── 热收缩包装机
```

适合使用层级 taxonomy 的原因：

- 分类具有父子关系；
- 需要独立分类页面；
- 需要分类导航；
- 需要继承筛选配置；
- 需要显示分类内容和分类图片。

## 3. 离散型产品属性

以下属性使用 taxonomy：

```text
product_brand
product_voltage
product_feature
product_material
product_application
product_automation
product_installation
product_output_type
product_protection
product_packaging_type
product_conveyor_type
product_detection_type
```

适合使用 taxonomy 的数据通常具有这些特点：

- 选项相对固定；
- 多个产品重复使用；
- 需要后台统一管理；
- 需要筛选；
- 需要统计数量；
- 可能需要独立归档或扩展。

## 4. 数值型产品属性

以下数据使用 post meta：

```text
product_price
product_power
product_rpm
product_width
product_detection_distance
product_speed
```

适合使用 post meta 的数据通常具有这些特点：

- 每个产品的值可能不同；
- 需要数值比较；
- 需要范围筛选；
- 需要数值排序；
- 不适合作为固定选项管理。

---

# taxonomy 与 post meta 如何选择

## 使用 taxonomy

例如：

```text
品牌
电压
材质
应用行业
安装方式
防护等级
```

原因：

```text
固定选项
+ 可复用
+ 可管理
+ 可筛选
+ 可统计
```

## 使用 post meta

例如：

```text
价格
功率
转速
宽度
运行速度
```

原因：

```text
数值变化多
+ 需要范围比较
+ 需要排序
```

## 判断原则

```text
固定、重复、可枚举
→ taxonomy

连续数值、范围比较
→ post meta
```

---

# 项目目录结构

```text
wp-product-navigation-filter-lab/
├── README.md
├── FILTER-FLOW.md
├── style.css
├── functions.php
├── header.php
├── footer.php
├── front-page.php
├── index.php
├── archive-product.php
├── taxonomy-product_category.php
├── single-product.php
├── screenshot.png
│
├── inc/
│   ├── filter-schema.php
│   ├── filter-request.php
│   ├── filter-query.php
│   ├── filter-cache.php
│   ├── filter-facets.php
│   └── filter-ajax.php
│
├── template-parts/
│   └── product/
│       ├── breadcrumbs.php
│       ├── category-hero.php
│       ├── category-navigation.php
│       ├── filter-form.php
│       ├── results.php
│       ├── card.php
│       ├── no-results.php
│       └── category-bottom-content.php
│
└── assets/
    ├── css/
    │   └── product.css
    ├── js/
    │   └── product-filter.js
    └── images/
```

---

# 筛选代码阅读顺序

推荐按照下面的顺序阅读：

```text
1. inc/filter-schema.php
2. inc/filter-request.php
3. inc/filter-query.php
4. inc/filter-cache.php
5. inc/filter-facets.php
6. inc/filter-ajax.php
7. template-parts/product/filter-form.php
8. template-parts/product/results.php
9. assets/js/product-filter.js
10. assets/css/product.css
```

详细流程说明见：

```text
FILTER-FLOW.md
```

---

# 筛选完整数据流

```text
用户选择条件
↓
HTML 表单产生参数
↓
JavaScript 使用 URLSearchParams 收集参数
↓
AJAX 请求发送 query 字符串
↓
WordPress 验证 Nonce
↓
pfl_get_filter_request() 清理并验证参数
↓
pfl_build_product_query_args() 生成查询参数
↓
WP_Query 查询产品
↓
pfl_get_faceted_filter_state() 计算联动数量
↓
WordPress 返回 JSON
↓
JavaScript 替换产品结果
↓
更新产品数量、分页和筛选项数量
↓
history.pushState() 同步地址栏
```

普通 GET 页面和 AJAX 请求使用同一套参数验证和查询规则。

---

# 筛选 Schema

文件：

```text
inc/filter-schema.php
```

核心函数：

```php
pfl_get_product_filter_schema()
```

Schema 是整个筛选系统的配置中心。

## taxonomy 筛选示例

```php
'brand' => [
    'label'    => '产品品牌',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_brand',
    'operator' => 'IN',
    'input'    => 'checkbox',
],
```

这段配置决定：

- URL 参数名是 `brand`；
- 数据来自 `product_brand`；
- 同组多个选项使用 `IN`；
- 前端使用复选框；
- 后端自动生成白名单；
- 查询系统自动生成 `tax_query`；
- Facet 系统自动计算数量。

## AND 筛选示例

```php
'feature' => [
    'label'    => '功能特点',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_feature',
    'operator' => 'AND',
    'input'    => 'checkbox',
],
```

用户同时选择：

```text
全自动
触屏控制
```

含义是：

```text
产品必须同时具备这两个功能
```

## 数值范围示例

```php
'price_range' => [
    'label'    => '价格范围',
    'type'     => 'range',
    'meta_key' => 'product_price',
    'options'  => [
        'under-10000' => [
            'label'   => '1 万以下',
            'value'   => 10000,
            'compare' => '<',
        ],
        '10000-30000' => [
            'label'   => '1 万～3 万',
            'value'   => [10000, 30000],
            'compare' => 'BETWEEN',
        ],
    ],
],
```

---

# 分类专属筛选与继承

不同产品分类可以显示不同的筛选组。

例如：

```text
包装设备
→ 品牌、电压、包装方式、自动化程度、价格、功率

输送设备
→ 品牌、材质、输送方式、宽度、速度、价格

电机
→ 品牌、电压、安装方式、防护等级、功率、转速

传感器
→ 品牌、检测方式、输出类型、防护等级、检测距离
```

当子分类没有单独配置时，系统按以下顺序查找：

```text
当前分类配置
↓
父分类配置
↓
更高层祖先配置
↓
默认配置
```

核心函数：

```php
pfl_resolve_filter_profile()
pfl_get_active_filter_keys()
```

分类筛选方案保存在 term meta 中。

---

# 请求清理与参数白名单

文件：

```text
inc/filter-request.php
```

核心入口：

```php
$request = pfl_get_filter_request(
    $_GET,
    $term,
    $paged
);
```

标准返回结构：

```php
[
    'source'        => [],
    'active_keys'   => [],
    'paged'         => 1,
    'applied_count' => 0,
]
```

## 为什么不能直接使用 `$_GET`

URL 可以被用户手动修改：

```text
?brand[]=not-exists
&price_range=invalid
&unknown=value
```

因此必须：

- `wp_unslash()`；
- `sanitize_key()`；
- `sanitize_title()`；
- 检查 Schema 中是否存在参数；
- 检查 taxonomy term slug 是否有效；
- 检查 range 选项是否有效；
- 删除未知参数。

只有通过验证的条件才进入查询。

核心函数：

```php
pfl_get_filter_request()
pfl_get_filter_values()
pfl_get_filter_value()
pfl_get_allowed_taxonomy_slugs()
pfl_sanitize_filter_keys()
```

---

# tax_query 与 meta_query

文件：

```text
inc/filter-query.php
```

## taxonomy 的 IN

```php
[
    'taxonomy' => 'product_brand',
    'field'    => 'slug',
    'terms'    => ['brand-a', 'brand-b'],
    'operator' => 'IN',
]
```

含义：

```text
品牌 A 或 品牌 B
```

## taxonomy 的 AND

```php
[
    'taxonomy' => 'product_feature',
    'field'    => 'slug',
    'terms'    => ['automatic', 'touch-screen'],
    'operator' => 'AND',
]
```

含义：

```text
同时具备 automatic 和 touch-screen
```

## 多个筛选组之间

最外层使用：

```php
'relation' => 'AND'
```

例如：

```text
品牌 A
AND
电压 380V
AND
功能：全自动 + 触屏控制
```

## 数值范围

```php
[
    'key'     => 'product_price',
    'value'   => [10000, 30000],
    'type'    => 'NUMERIC',
    'compare' => 'BETWEEN',
]
```

数值字段必须使用：

```php
'type' => 'NUMERIC'
```

否则数据库可能按照字符串比较数字。

---

# 普通页面与 AJAX 查询

## 普通页面

普通产品归档和产品分类页使用：

```php
pre_get_posts
```

核心函数：

```php
pfl_filter_product_main_query()
```

优点：

- 修改 WordPress 主查询；
- 模板可以继续使用标准 Loop；
- 分页逻辑更自然；
- 不需要在模板中重复执行产品查询。

## AJAX

AJAX 使用：

```php
pfl_build_product_query_args()
```

生成完整的 `WP_Query` 参数。

普通页面和 AJAX 都使用：

```php
pfl_get_product_query_parts()
```

因此筛选条件、排序和范围逻辑保持一致。

---

# AJAX 筛选

文件：

```text
inc/filter-ajax.php
```

核心入口：

```php
pfl_ajax_filter_products()
```

注册：

```php
wp_ajax_pfl_filter_products
wp_ajax_nopriv_pfl_filter_products
```

安全校验：

```php
check_ajax_referer(
    'pfl_filter_products',
    'nonce'
);
```

返回数据包括：

```text
产品结果 HTML
产品总数
总页数
当前页
已选条件数量
Facet 联动数量
缓存调试信息
当前状态 URL
屏幕阅读器提示
```

## 渐进增强

JavaScript 可用：

```text
AJAX 无刷新筛选
```

JavaScript 不可用或请求失败：

```text
普通 GET 表单
```

这样可以兼顾：

- 可靠性；
- 可访问性；
- 调试便利；
- URL 分享；
- 搜索引擎抓取基础页面。

---

# 前端 URL 与浏览器历史

文件：

```text
assets/js/product-filter.js
```

使用：

```text
URLSearchParams
history.pushState()
popstate
```

## URL 的作用

筛选状态保存在 URL 中：

```text
/products/
?brand[]=huadong
&voltage[]=380v
&price_range=10000-30000
```

优点：

- 可以刷新；
- 可以复制；
- 可以分享；
- 可以收藏；
- 浏览器前进和后退可以恢复；
- 普通 GET 和 AJAX 状态一致。

## 状态边界

真正的筛选条件保存在 URL。

`localStorage` 只保存界面状态：

```text
筛选组展开或收起
显示更多或收起
```

不保存：

```text
产品结果
分页结果
筛选条件
AJAX 响应
```

---

# AbortController 与请求防抖

连续点击筛选条件时，旧请求可能比新请求更晚返回。

系统使用：

```javascript
new AbortController()
```

在发送新请求前取消旧请求，避免旧结果覆盖新结果。

同时使用防抖：

```text
用户连续操作
↓
等待短时间
↓
只发送最后一次请求
```

减少无意义请求。

---

# Faceted Search 联动计数

文件：

```text
inc/filter-facets.php
```

核心函数：

```php
pfl_get_faceted_filter_state()
```

## 为什么要排除当前组

当前条件：

```text
品牌：华东机械
电压：380V
```

计算“品牌”数量时，如果仍然保留“华东机械”，其他品牌都会变成 0。

正确做法：

```text
计算品牌组
→ 保留电压等其他组
→ 暂时移除品牌组
→ 分别计算每个品牌
```

这叫做：

```text
排除当前 Facet 组
```

## IN 组计数

品牌、电压、材质等使用 `IN`。

数量表示：

```text
选择该选项后能够得到多少产品
```

## AND 组计数

功能特点使用 `AND`。

已经选择：

```text
全自动
触屏控制
```

未选中的“不锈钢机身”数量表示：

```text
同时具备全自动、触屏控制和不锈钢机身的产品数量
```

## 零结果状态

```text
数量为 0 且未选中
→ 禁用

数量为 0 但已经选中
→ 保持可操作，以便取消
```

---

# Facet 缓存

文件：

```text
inc/filter-cache.php
```

联动数量可能需要多次查询，因此使用：

```php
get_transient()
set_transient()
```

缓存键由以下内容生成：

```text
缓存版本
当前产品分类 ID
当前筛选方案
已经应用的筛选条件
```

## 自动失效

以下变化会提升缓存版本：

- 产品保存；
- 产品删除；
- 产品分类变化；
- 产品属性变化；
- 属性 term 新建；
- 属性 term 编辑；
- 属性 term 删除。

核心函数：

```php
pfl_get_facet_cache_version()
pfl_bump_facet_cache_version()
```

采用缓存版本的优点：

```text
不需要搜索并删除所有旧 transient
```

旧缓存自然过期，新请求自动使用新版本缓存键。

---

# 分页与排序

筛选系统支持：

- 最新发布；
- 价格升序；
- 价格降序；
- 标题排序；
- AJAX 分页；
- 普通 GET 分页；
- 当前页状态；
- 修改筛选后回到第一页；
- 分页后定位产品结果区域；
- 地址栏保留筛选条件和页码。

分页 HTML 由普通页面和 AJAX 共用：

```php
pfl_get_product_pagination_html()
```

---

# 电脑端筛选体验

主要样式文件：

```text
assets/css/product.css
```

桌面端重点：

- 标题列固定宽度；
- 中文标题保持横向；
- 筛选内容使用独立列；
- 收起组只保留一行；
- 已选标签限制最大高度；
- 搜索框不无限拉伸；
- 已选项优先显示；
- 零结果项显示禁用状态；
- 结果加载时显示骨架状态；
- 筛选摘要可以吸顶。

---

# 手机端筛选体验

手机端使用筛选抽屉。

主要设计：

```text
顶部：标题 + 关闭按钮
中部：筛选组
底部：清空条件 + 查看产品数量
```

技术重点：

- `height: 100dvh`；
- `env(safe-area-inset-bottom)`；
- 背景滚动锁定；
- 遮罩关闭；
- `Esc` 关闭；
- 焦点循环；
- 关闭后焦点返回；
- 触控区域至少 44px；
- AJAX 后产品数量实时更新。

---

# 无障碍设计

项目包含：

- `aria-expanded`；
- `aria-controls`；
- `aria-disabled`；
- `aria-live`；
- `aria-busy`；
- 键盘操作；
- 抽屉焦点循环；
- 关闭后焦点返回；
- 屏幕阅读器结果播报。

筛选组折叠使用按钮，而不是只给标题绑定点击事件。

---

# 分类导航查询优化

多层级分类导航如果在循环中不断调用：

```php
get_terms()
```

容易产生 N+1 查询。

本项目采用：

```text
一次读取全部产品分类
↓
建立 by_id 索引
↓
建立 by_parent 索引
↓
在内存中生成当前路径和下一层
```

核心函数位于 `functions.php`：

```php
pfl_get_product_category_index()
pfl_get_current_product_category_path()
pfl_get_category_navigation_levels()
```

---

# SEO 与结构化数据

虽然项目重点是筛选，但仍保留基础 SEO 能力：

- 产品归档 Canonical；
- 产品分类 Canonical；
- 产品详情 Canonical；
- 筛选参数页 `noindex, follow`；
- 分类 SEO 标题；
- 分类 Meta Description；
- 产品详情 Meta Description；
- Product JSON-LD；
- BreadcrumbList JSON-LD；
- CollectionPage JSON-LD；
- ItemList、Offer、Brand 和 PropertyValue。

这些功能保持稳定，但后续不再继续扩展。

---

# 安装方法

## 方法一：后台上传

1. 下载主题 ZIP；
2. 进入 WordPress 后台；
3. 打开“外观 → 主题”；
4. 点击“安装主题”；
5. 上传 ZIP；
6. 启用主题。

## 方法二：手动安装

将主题目录复制到：

```text
wp-content/themes/
```

最终目录：

```text
wp-content/themes/wp-product-navigation-filter-lab/
```

然后在后台启用。

---

# 初始化演示数据

启用主题后进入：

```text
外观
→ 产品主题演示数据
```

执行演示数据导入。

导入内容包括：

- 产品分类；
- 分类层级；
- 产品属性 term；
- 演示产品；
- 产品价格和数值属性；
- 分类专属筛选方案；
- 分类图片和分类内容。

随后进入：

```text
设置
→ 固定链接
→ 保存更改
```

---

# 调试方法

## 开启筛选调试

在 `wp-config.php` 中加入：

```php
define('PFL_FILTER_DEBUG', true);
```

管理员登录后会看到：

```text
实时计算 · 18.42 ms
缓存命中 · 0.36 ms
```

## 调试顺序

出现筛选错误时，按以下顺序检查：

```text
1. 地址栏参数
2. Schema 参数名
3. 参数是否通过白名单
4. tax_query
5. meta_query
6. WP_Query 产品结果
7. Facet 数量
8. AJAX JSON
9. JavaScript DOM 更新
10. 浏览器历史状态
```

## 浏览器重点检查

- Console 是否有 JavaScript 错误；
- Network 中 AJAX 是否成功；
- AJAX 返回是否为 JSON；
- Nonce 是否有效；
- 返回产品数量是否正确；
- Facet 数量是否和结果一致；
- URL 是否同步；
- 前进和后退是否恢复。

---

# 普通 GET 与 AJAX 一致性测试

## 普通 GET

临时禁用 JavaScript，提交筛选表单：

- 检查 URL；
- 检查产品结果；
- 检查排序；
- 检查分页。

## AJAX

重新启用 JavaScript：

- 选择同样条件；
- 比较产品结果；
- 比较产品数量；
- 比较分页；
- 比较 URL 参数。

原则：

```text
同一组条件
→ 普通 GET 与 AJAX 必须得到相同结果
```

---

# 推荐测试清单

## 数据逻辑

- 产品总归档正常；
- 产品分类页正常；
- 分类专属筛选组正确；
- 子分类继承正确；
- taxonomy `IN` 正确；
- taxonomy `AND` 正确；
- 数值范围边界正确；
- 排序正确；
- 分页正确。

## AJAX

- 条件变化后自动更新；
- 快速点击不会被旧请求覆盖；
- 产品数量正确；
- Facet 数量正确；
- 零结果项禁用；
- 地址栏同步；
- 刷新后状态恢复；
- 前进和后退正常；
- 请求失败入口正常。

## 电脑端

- 分类标题不竖排；
- 收起组高度正常；
- 已选标签不会无限撑高；
- 搜索框布局正常；
- 筛选项对齐；
- 吸顶摘要正常；
- 骨架加载正常。

## 手机端

- 抽屉正常打开和关闭；
- 遮罩关闭正常；
- `Esc` 正常；
- 背景不会滚动；
- 头部和底部固定；
- 安全区域正常；
- 触控尺寸足够；
- 查看产品按钮数量正确。

---

# 如何新增 taxonomy 筛选

以“产品颜色”为例。

## 第一步：注册 taxonomy

在 `functions.php` 中注册：

```php
register_taxonomy(
    'product_color',
    ['product'],
    [
        'label'        => '产品颜色',
        'public'       => true,
        'hierarchical' => false,
        'show_admin_column' => true,
    ]
);
```

## 第二步：增加 Schema

在 `inc/filter-schema.php` 中增加：

```php
'color' => [
    'label'    => '产品颜色',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_color',
    'operator' => 'IN',
    'input'    => 'checkbox',
],
```

## 第三步：加入分类筛选方案

将：

```text
color
```

加入需要显示颜色筛选的分类方案。

完成后，以下逻辑会自动复用：

- 参数白名单；
- 筛选表单；
- `tax_query`；
- AJAX；
- 联动计数；
- 零结果禁用；
- URL 状态。

---

# 如何新增数值范围筛选

以“产品重量”为例。

## 第一步：增加产品 meta

```text
product_weight
```

## 第二步：增加 Schema

```php
'weight_range' => [
    'label'    => '产品重量',
    'type'     => 'range',
    'meta_key' => 'product_weight',
    'options'  => [
        'under-10' => [
            'label'   => '10kg 以下',
            'value'   => 10,
            'compare' => '<',
        ],
        '10-50' => [
            'label'   => '10～50kg',
            'value'   => [10, 50],
            'compare' => 'BETWEEN',
        ],
        'over-50' => [
            'label'   => '50kg 以上',
            'value'   => 50,
            'compare' => '>',
        ],
    ],
],
```

## 第三步：加入分类筛选方案

把：

```text
weight_range
```

加入需要显示该筛选的分类。

系统会自动复用范围验证、`meta_query`、AJAX 和 Facet 计数。

---

# 重要设计原则

## 1. Schema 是唯一配置源

不要分别在：

- 表单；
- 查询；
- AJAX；
- 白名单；
- 已选标签；

重复写一套筛选定义。

正确做法：

```text
只在 Schema 中定义
↓
其他模块读取 Schema
```

## 2. URL 是筛选状态的唯一事实来源

```text
筛选条件
→ URL

界面展开状态
→ localStorage
```

不要把真正筛选条件只保存在 JavaScript 内存中。

## 3. 普通 GET 是基础，AJAX 是增强

```text
GET 保证可靠性
AJAX 提升体验
```

## 4. 查询与显示分离

```text
请求模块
→ 只处理参数

查询模块
→ 只处理 WP_Query

Facet 模块
→ 只处理联动数量

模板
→ 只处理 HTML

JavaScript
→ 只处理交互与状态同步
```

## 5. 当前组计数必须排除自身

这是 Faceted Search 正确计算候选数量的关键。

## 6. 已选的零结果项不能被锁死

否则用户无法取消造成零结果的条件。

## 7. taxonomy 与 meta 不应混用

固定选项优先 taxonomy，连续数值优先 post meta。

## 8. 不在模板中重复执行主查询

普通归档优先使用：

```php
pre_get_posts
```

## 9. 筛选参数必须经过白名单

不要直接把 `$_GET` 传入 `WP_Query`。

## 10. 大量查询必须考虑缓存与失效

缓存不仅要会写，还必须考虑数据变化后的失效。

---

# 版本历程

| 版本 | 主要内容 |
|---|---|
| v1.0.0 | 产品分类导航与基础筛选 |
| v1.1.0 | 筛选体验和查询优化 |
| v1.2.0 | AJAX、分页和浏览器历史 |
| v1.3.0 | 动态属性与分类专属方案 |
| v1.4.0 | 分类内容、SEO 和结构化数据 |
| v1.5.0 | 联动计数、零结果禁用和缓存 |
| v1.6.0 | 手机抽屉、折叠、搜索和更多 |
| v1.6.1 | 电脑端筛选布局修复 |
| v1.7.0 | 筛选模块化、数据流统一与体验收敛 |

---

# 当前状态

```text
v1.7.0 Stable
```

项目主体开发已经完成。

后续只在以下情况下发布补丁版本：

```text
v1.7.1：修复明确错误
v1.7.2：修复浏览器或 WordPress 兼容性
v1.7.3：补充注释和文档
```

不再为了增加版本号继续堆叠功能。

---

# 学习建议

完成安装和测试后，可以围绕现有代码进行以下练习：

1. 新增一个 taxonomy 筛选；
2. 新增一个数值范围筛选；
3. 把一个筛选组从 `IN` 改为 `AND`；
4. 临时关闭 AJAX，观察普通 GET；
5. 打印最终 `tax_query` 和 `meta_query`；
6. 查看 AJAX JSON；
7. 开启 `PFL_FILTER_DEBUG`；
8. 观察 Facet 缓存是否命中；
9. 手动追踪一个条件从 HTML 到 `WP_Query`；
10. 比较电脑端和手机端的同一筛选流程。

---

# 相关文档

- `README.md`：项目总览和重要知识点；
- `FILTER-FLOW.md`：筛选代码完整执行流程；
- `RELEASE-product-navigation-filter-v1.7.0.md`：v1.7.0 发布说明。

---

# License

本项目采用：

```text
GPL-2.0-or-later
```

可用于学习、修改和二次开发。
