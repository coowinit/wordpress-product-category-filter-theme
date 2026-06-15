# WordPress 产品分类导航与动态筛选主题

> 一套专注研究 WordPress 产品筛选代码的经典主题，覆盖 Schema、参数验证、`tax_query`、`meta_query`、AJAX、联动计数、分页、URL 状态，以及桌面端和手机端筛选体验。

![Version](https://img.shields.io/badge/version-v1.7.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目定位

本项目不再继续增加收藏、产品对比、购物车、会员等业务功能。

后续只围绕以下内容维护：

```text
产品筛选配置
筛选参数验证
taxonomy 与 post meta 查询
AJAX 无刷新更新
联动筛选数量
缓存
分页和浏览器历史
桌面端筛选体验
手机端筛选体验
```

当前版本：

```text
v1.7.0
```

---

# v1.7.0 核心更新

## 筛选 PHP 模块化

与筛选直接相关的代码从大型 `functions.php` 中拆分为：

```text
inc/
├── filter-schema.php
├── filter-request.php
├── filter-query.php
├── filter-cache.php
├── filter-facets.php
└── filter-ajax.php
```

`functions.php` 继续保留：

- 主题初始化；
- `product` 自定义文章类型；
- 产品分类与属性 taxonomy；
- 产品自定义字段；
- 分类导航；
- 分类内容和已有 SEO；
- 演示数据。

这种结构既保留完整主题，又可以单独阅读筛选知识。

---

## 统一 GET 与 AJAX 请求

新增：

```php
pfl_get_filter_request()
```

普通 GET 页面和 AJAX 请求都先转换成同一种标准结构：

```php
[
    'source'        => [],
    'active_keys'   => [],
    'paged'         => 1,
    'applied_count' => 0,
]
```

参数只清理和验证一次，避免普通页面与 AJAX 使用不同规则。

---

## 统一查询参数

新增：

```php
pfl_build_product_query_args()
```

集中生成：

- 产品文章类型；
- 产品分类；
- `tax_query`；
- `meta_query`；
- 排序；
- 分页。

AJAX 不再单独重复拼装一套查询代码。

---

## 简化 AJAX 错误处理

删除自动重试逻辑。

现在请求失败后直接显示：

```text
重新请求
使用普通页面打开
```

这样减少分支、避免重复请求，也更方便学习 AJAX 的完整执行流程。

---

## 筛选调试开关

性能提示不再自动跟随 `WP_DEBUG`。

需要调试筛选时，在 `wp-config.php` 中单独启用：

```php
define('PFL_FILTER_DEBUG', true);
```

只有管理员会看到缓存命中和计数耗时。

---

## 桌面端体验收敛

- 保留稳定的标题列与内容列；
- 防止中文标题逐字换行；
- 收起的筛选组只保留一行；
- 已选标签区域限制高度并允许内部滚动；
- 搜索框保持合理宽度；
- 筛选项和组标题继续保持清晰对齐。

## 手机端体验收敛

- 抽屉使用 `100dvh`；
- 抽屉头部保持固定；
- 底部操作栏保持固定；
- 支持手机安全区域；
- 筛选项触控高度不低于 44px；
- 长文本可以自然换行；
- 保留遮罩、Esc、焦点循环和背景滚动锁定。

---

# 筛选代码阅读顺序

```text
1. inc/filter-schema.php
2. inc/filter-request.php
3. inc/filter-query.php
4. inc/filter-cache.php
5. inc/filter-facets.php
6. inc/filter-ajax.php
7. template-parts/product/filter-form.php
8. assets/js/product-filter.js
9. assets/css/product.css
```

详细讲解：

```text
FILTER-FLOW.md
```

---

# 文件结构

```text
wp-product-navigation-filter-lab/
├── functions.php
├── FILTER-FLOW.md
├── README.md
├── style.css
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
│       ├── filter-form.php
│       ├── results.php
│       ├── card.php
│       └── no-results.php
│
└── assets/
    ├── css/product.css
    └── js/product-filter.js
```

---

# 核心筛选流程

```text
HTML 表单
↓
URLSearchParams
↓
WordPress AJAX
↓
pfl_get_filter_request()
↓
pfl_build_product_query_args()
↓
WP_Query
↓
pfl_get_faceted_filter_state()
↓
JSON
↓
产品结果与筛选数量更新
↓
history.pushState()
```

---

# 升级方式

主题目录名称保持不变，可直接覆盖 v1.6.1。

升级后建议：

```text
1. 清除浏览器缓存
2. 清除站点页面缓存和对象缓存
3. 设置 → 固定链接 → 保存更改
4. 回归测试普通 GET 和 AJAX
5. 测试电脑端和手机端筛选
```

本版主要是代码重构，不要求重新导入演示产品。

---

# 推荐测试

## 普通 GET

暂时禁用 JavaScript，提交筛选表单，确认：

- URL 参数正确；
- 产品查询正确；
- 排序和分页正确。

## AJAX

确认：

- 筛选条件自动更新；
- 产品数量和 Facet 数量一致；
- 地址栏同步；
- 前进和后退可恢复；
- 失败时出现手动重试和普通页面入口。

## 桌面端

确认：

- 标题不换成竖排；
- 收起组高度正常；
- 已选标签较多时不无限撑高页面；
- 搜索框和选项对齐。

## 手机端

确认：

- 抽屉高度适配浏览器工具栏；
- 头部和底部操作栏固定；
- 按钮触控区域足够；
- 遮罩、Esc 和焦点返回正常。

---

# 项目边界

明确不增加：

- 产品收藏；
- 产品对比；
- 购物车；
- 订单和支付；
- 会员系统；
- 推荐系统；
- 前端大型框架。

后续版本只对筛选代码、性能、兼容性和桌面端/手机端体验进行维护。

---

# 版本记录

## v1.7.0

- 将筛选代码拆分为 6 个模块；
- 新增标准筛选请求结构；
- GET 与 AJAX 共用参数验证；
- AJAX 使用统一 WP_Query 参数构建；
- 删除 AJAX 自动重试；
- 增加独立筛选调试开关；
- 优化桌面端已选标签和折叠组；
- 优化手机端动态视口和触控尺寸；
- 新增 `FILTER-FLOW.md` 学习文档；
- 明确项目以后只研究产品筛选。

## v1.6.1

- 修复桌面端筛选标题逐字换行；
- 优化桌面端筛选组布局。

## v1.6.0

- 移动端筛选抽屉；
- 筛选组折叠；
- 显示更多；
- 组内搜索；
- 已选项置顶。

## v1.5.0

- 联动筛选数量；
- 零结果禁用；
- Facet 缓存。

## v1.4.0

- 分类内容与基础 SEO。

## v1.3.0

- 动态产品属性与分类专属筛选方案。

## v1.2.0

- AJAX 无刷新筛选与浏览器历史记录。

## v1.1.0

- 筛选体验和查询优化。

## v1.0.0

- 产品分类导航和多条件筛选基础版。

---

## License

GPL-2.0-or-later
