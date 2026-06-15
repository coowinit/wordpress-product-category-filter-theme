# 产品筛选代码流程指南

> 本文只讲本主题中的“产品筛选”代码，不讨论收藏、对比、购物车等其他业务。

---

## 1. 推荐阅读顺序

```text
inc/filter-schema.php
↓
inc/filter-request.php
↓
inc/filter-query.php
↓
inc/filter-cache.php
↓
inc/filter-facets.php
↓
inc/filter-ajax.php
↓
template-parts/product/filter-form.php
↓
assets/js/product-filter.js
```

---

## 2. 完整数据流

```text
用户选择筛选条件
↓
HTML 表单产生参数
↓
JavaScript 使用 URLSearchParams 收集参数
↓
AJAX 将 query 字符串发送到 WordPress
↓
pfl_get_filter_request() 清理并验证参数
↓
pfl_build_product_query_args() 生成 WP_Query 参数
↓
WP_Query 查询产品
↓
pfl_get_faceted_filter_state() 计算筛选项数量
↓
WordPress 返回 JSON
↓
JavaScript 替换产品结果并更新数量
↓
history.pushState() 同步地址栏
```

普通 GET 页面和 AJAX 请求都通过同一套参数验证及查询函数。

---

# 3. 筛选配置

文件：

```text
inc/filter-schema.php
```

核心函数：

```php
pfl_get_product_filter_schema()
```

每个筛选组由一个配置数组定义。

## taxonomy 示例

```php
'brand' => [
    'label'    => '产品品牌',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_brand',
    'operator' => 'IN',
    'input'    => 'checkbox',
],
```

含义：

- URL 参数名：`brand`
- 数据来源：`product_brand`
- 同组多个选择使用 `IN`
- 前端使用复选框

## AND 示例

```php
'feature' => [
    'label'    => '功能特点',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_feature',
    'operator' => 'AND',
    'input'    => 'checkbox',
],
```

选择多个功能后，产品必须同时具备这些功能。

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
    ],
],
```

---

# 4. 请求清理与白名单

文件：

```text
inc/filter-request.php
```

核心入口：

```php
$request = pfl_get_filter_request($_GET, $term, $paged);
```

返回结构：

```php
[
    'source'        => [],
    'active_keys'   => [],
    'paged'         => 1,
    'applied_count' => 0,
]
```

## 为什么不能直接使用 `$_GET`

用户可以手动修改 URL。

例如：

```text
?brand[]=not-exists
&price_range=invalid
```

因此必须执行：

```text
wp_unslash
sanitize_title
sanitize_key
term slug 白名单验证
Schema 选项验证
```

只有通过验证的参数才会进入 `source`。

---

# 5. 生成查询参数

文件：

```text
inc/filter-query.php
```

## 查询片段

```php
$parts = pfl_get_product_query_parts(
    $request['source'],
    $request['active_keys']
);
```

返回：

```php
[
    'tax_clauses'  => [],
    'meta_clauses' => [],
    'sort_args'    => [],
]
```

## 完整 AJAX 查询参数

```php
$query_args = pfl_build_product_query_args(
    $request,
    $term,
    9
);
```

该函数统一处理：

- 产品文章类型
- 产品分类
- `tax_query`
- `meta_query`
- 排序
- 分页

## 普通页面

普通产品归档通过：

```php
pre_get_posts
```

修改 WordPress 主查询。

这样不会在模板中重新执行另一套产品查询。

---

# 6. taxonomy 的 IN 与 AND

## IN

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

## AND

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
同时具备全自动和触屏控制
```

---

# 7. 数值范围筛选

价格、功率和转速等数据保存在 post meta 中。

示例：

```php
[
    'key'     => 'product_price',
    'value'   => [10000, 30000],
    'type'    => 'NUMERIC',
    'compare' => 'BETWEEN',
]
```

必须指定：

```php
'type' => 'NUMERIC'
```

否则 WordPress 可能按照字符串比较数字。

---

# 8. 联动数量

文件：

```text
inc/filter-facets.php
```

核心函数：

```php
pfl_get_faceted_filter_state()
```

## 为什么计算时排除当前组

当前已选择：

```text
品牌：华东机械
电压：380V
```

计算品牌数量时，应当：

```text
保留 380V
暂时移除品牌条件
分别计算每个品牌
```

否则其他品牌会因为“华东机械”条件仍然存在而全部变成 0。

## 零结果状态

```text
count = 0 且未选中
→ disabled

count = 0 但已经选中
→ 仍可取消
```

---

# 9. 缓存

文件：

```text
inc/filter-cache.php
```

联动计数可能需要多次查询，因此使用：

```php
set_transient()
get_transient()
```

缓存键由以下内容组成：

```text
缓存版本
当前产品分类
当前筛选方案
已经应用的筛选条件
```

产品或属性发生变化后：

```php
pfl_bump_facet_cache_version()
```

新请求会使用新的缓存键，旧缓存等待自然过期。

## 开启性能提示

在 `wp-config.php` 中加入：

```php
define('PFL_FILTER_DEBUG', true);
```

管理员登录后可以看到：

```text
实时计算 · 18.42 ms
缓存命中 · 0.36 ms
```

---

# 10. AJAX

文件：

```text
inc/filter-ajax.php
```

入口：

```php
pfl_ajax_filter_products()
```

安全校验：

```php
check_ajax_referer(
    'pfl_filter_products',
    'nonce'
);
```

AJAX 返回：

```text
产品结果 HTML
产品总数
分页
已选条件数量
联动 Facet 数量
缓存调试信息
当前状态 URL
屏幕阅读器提示
```

AJAX 失败后不再自动重复请求。

页面会提供：

```text
重新请求
使用普通页面打开
```

这样执行流程更容易理解，也避免意外重复请求。

---

# 11. 前端 JavaScript

文件：

```text
assets/js/product-filter.js
```

主要区域：

```text
1. DOM 与运行环境
2. 前端界面状态
3. 表单与 URL 状态
4. 筛选组折叠、搜索和更多
5. AJAX 结果与联动数量
6. 移动端筛选抽屉
7. AJAX 请求
8. 事件绑定和初始化
```

核心函数：

| 目标 | 函数 |
|---|---|
| 收集表单参数 | `getFormParams()` |
| 生成可分享 URL | `buildClientStateUrl()` |
| 发起筛选请求 | `requestProducts()` |
| 更新产品结果 | `replaceResults()` |
| 更新筛选计数 | `updateFacetState()` |
| 打开手机抽屉 | `openDrawer()` |
| 关闭手机抽屉 | `closeDrawer()` |
| URL 恢复表单 | `syncFormFromUrl()` |

真正的筛选状态由 URL 管理。

`localStorage` 只保存：

```text
筛选组展开状态
显示更多状态
```

---

# 12. 桌面端与手机端

## 桌面端

重点代码位于：

```text
assets/css/product.css
@media (min-width: 821px)
```

主要目标：

- 标题不逐字换行
- 折叠组只占一行
- 已选标签区域不过度增高
- 搜索框保持合理宽度
- 筛选项和标题对齐

## 手机端

重点代码位于：

```text
assets/css/product.css
@media (max-width: 820px)
```

主要目标：

- 使用 `100dvh`
- 抽屉头部固定
- 底部操作栏固定
- 支持安全区域
- 每个触控项至少 44px
- 打开抽屉时锁定背景滚动
- 支持 Esc、遮罩和焦点循环

---

# 13. 常见修改

## 新增 taxonomy 筛选

1. 在 `functions.php` 注册 taxonomy；
2. 在 `filter-schema.php` 增加 Schema；
3. 为产品设置对应 term；
4. 将筛选组加入分类专属方案。

查询、参数验证、AJAX 和联动数量会自动复用 Schema。

## 新增数值范围筛选

1. 在产品字段配置中增加 meta；
2. 在 Schema 中增加 `range`；
3. 配置 `meta_key` 和区间；
4. 保存产品数值。

## 把 OR 改为 AND

```php
'operator' => 'AND'
```

仅适用于 taxonomy 筛选组。

## 临时关闭 AJAX

不加载或禁用：

```text
assets/js/product-filter.js
```

普通 GET 表单仍然可以工作。

## 关闭性能提示

删除或改为：

```php
define('PFL_FILTER_DEBUG', false);
```

---

# 14. 调试顺序

出现筛选结果错误时，按以下顺序排查：

```text
1. 地址栏参数是否正确
2. Schema 参数名是否一致
3. pfl_get_filter_request() 是否保留了参数
4. tax_query / meta_query 是否正确
5. WP_Query 找到的产品是否正确
6. Facet 数量是否和产品结果一致
7. AJAX JSON 是否返回成功
8. JavaScript 是否正确更新 DOM
```

浏览器控制台重点检查：

```text
JavaScript 语法错误
AJAX HTTP 状态
返回 JSON 格式
按钮 disabled 状态
history.pushState URL
```

---

# 15. 本项目的边界

本项目以后只研究：

- 产品筛选配置
- 参数验证
- taxonomy 与 meta 查询
- AJAX
- 联动数量
- 缓存
- 分页与 URL
- 桌面端体验
- 手机端体验

不再扩展：

```text
收藏
对比
购物车
订单
支付
会员中心
推荐系统
```
