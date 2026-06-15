# WordPress 产品分类导航与动态筛选主题

> 一套用于学习 WordPress 产品目录系统的经典主题，完整演示多层级分类导航、动态产品属性、AJAX 无刷新筛选、联动计数、缓存优化、SEO 与结构化数据。

![Version](https://img.shields.io/badge/version-v1.5.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目简介

本项目是一套用于学习 WordPress 产品目录、产品属性与筛选系统的经典主题。

主题从自定义文章类型与分类法开始，逐步实现：

- 产品自定义文章类型；
- 多层级产品分类导航；
- 分类专属动态筛选组；
- 父分类筛选方案继承；
- AJAX 无刷新筛选和分页；
- GET 降级与浏览器历史记录；
- 分类图片、顶部内容和底部内容；
- Canonical、Robots 和结构化数据；
- 联动筛选项数量；
- 零结果选项自动禁用；
- 筛选计数缓存与自动失效。

当前版本：

```text
v1.5.0
```

v1.5.0 在 v1.4.0 内容与 SEO 增强版基础上，将筛选器升级为更接近真实电商和 B2B 产品目录的 Faceted Search 交互。

---

# v1.5.0 核心更新

## 联动筛选项数量

每个筛选项都会显示一个数量：

```text
华东机械（3）
蓝海设备（2）
启航工业（0）
```

这里的数量不再只是全站 term 数量，而是基于：

```text
当前产品分类
+
其他已经选择的筛选组
+
当前候选选项
```

实时计算。

例如当前已经选择：

```text
电压：380V
价格：1 万～3 万
```

品牌旁边的数量表示：

> 在 380V 和 1 万～3 万等其他条件下，该品牌自身可以匹配多少产品。

---

## 同组计算规则

### IN 组

适用于：

```text
品牌
电压
材质
应用行业
自动化程度
```

同组多个选择表示“或”。

联动数量计算时会暂时排除该组自身条件，再分别计算每个候选项。

### AND 组

功能特点使用：

```php
'operator' => 'AND'
```

例如已经选择：

```text
全自动
触屏控制
```

未选中的“不锈钢机身”数量表示：

> 同时具备全自动、触屏控制和不锈钢机身的产品数量。

### Range 组

适用于：

```text
价格
功率
转速
设备宽度
检测距离
运行速度
```

每个区间显示切换到该区间后的结果数量。

---

## 自动禁用零结果选项

当某个选项在当前条件组合下没有产品时：

```text
启航工业（0）
```

该选项会：

- 显示灰色禁用状态；
- 无法继续点击；
- 增加 `aria-disabled`；
- 保留数量 0，帮助用户理解为什么不可选。

已经处于选中状态的选项即使结果为 0，也不会强制禁用，用户仍可取消该条件。

---

## AJAX 同步更新

每次 AJAX 请求现在会同时返回：

```text
产品结果 HTML
产品总数
分页
地址栏状态
筛选项联动数量
不可用状态
计数性能信息
```

前端无需替换整个筛选表单，只更新每个选项的：

```text
数量
disabled
is-disabled
aria-disabled
```

这样可以减少 DOM 重建，并保留当前交互状态。

---

## 联动计数缓存

筛选计数结果使用 WordPress Transient 缓存：

```text
缓存维度：
分类 ID
筛选方案
已经应用的筛选状态
缓存版本
```

默认缓存时间：

```text
10 分钟
```

重复访问相同筛选状态时，可以直接命中缓存。

---

## 缓存自动失效

以下操作会自动提升筛选缓存版本：

- 新增或更新产品；
- 修改产品价格、功率等字段；
- 修改产品分类；
- 修改品牌、电压、功能等属性；
- 新建、编辑或删除产品属性 term；
- 调整产品与属性之间的关联。

主题使用版本号失效方式，而不是扫描并逐条删除所有 transient。

```text
旧缓存
仍然保留到自然过期

新请求
使用新的缓存版本键
```

这种方式更适合筛选组合较多的场景。

---

## 性能调试信息

当同时满足以下条件时：

```text
当前用户是管理员
WP_DEBUG = true
```

筛选状态区域会显示：

```text
实时计算 · 18.42 ms
```

或：

```text
缓存命中 · 0.36 ms
```

普通访客不会看到该性能信息。

---

# 完整功能

## 产品数据

- `product` 自定义文章类型；
- 产品标题、正文、摘要和特色图片；
- 产品型号；
- 产品价格；
- 产品功率；
- 产品转速；
- 产品宽度；
- 检测距离；
- 运行速度。

## 产品分类

- 多层级产品分类；
- 当前分类路径高亮；
- 父级和子级导航；
- 分类图片；
- 分类顶部内容；
- 分类底部内容；
- 分类 SEO 标题；
- 分类 Meta Description。

## 动态产品属性

- 品牌；
- 电压；
- 功能特点；
- 材质；
- 应用行业；
- 自动化程度；
- 安装方式；
- 输出类型；
- 防护等级；
- 包装方式；
- 输送方式；
- 检测方式。

## 筛选能力

- 分类专属筛选组；
- 父级配置继承；
- taxonomy `IN`；
- taxonomy `AND`；
- 数值范围筛选；
- 动态联动数量；
- 零结果选项禁用；
- AJAX 自动更新；
- AJAX 分页；
- 普通 GET 降级；
- 地址栏同步；
- 前进和后退恢复；
- 筛选结果缓存。

## SEO

- SEO 标题；
- Meta Description；
- Canonical；
- 筛选页 `noindex, follow`；
- Product JSON-LD；
- BreadcrumbList JSON-LD；
- CollectionPage JSON-LD；
- ItemList、Offer、Brand 和 PropertyValue。

---

# 文件结构

```text
wp-product-navigation-filter-lab/
├── style.css
├── functions.php
├── header.php
├── footer.php
├── front-page.php
├── index.php
├── archive-product.php
├── taxonomy-product_category.php
├── single-product.php
│
├── template-parts/
│   └── product/
│       ├── breadcrumbs.php
│       ├── category-hero.php
│       ├── category-navigation.php
│       ├── filter-form.php
│       ├── results.php
│       ├── no-results.php
│       ├── category-bottom-content.php
│       └── card.php
│
└── assets/
    ├── css/
    │   └── product.css
    ├── js/
    │   └── product-filter.js
    └── images/
```

---

# v1.5.0 重点代码

## 计算联动筛选状态

```php
pfl_get_faceted_filter_state()
```

## 获取排除当前组后的产品 ID

```php
pfl_get_faceted_base_product_ids()
```

## 数值区间判断

```php
pfl_numeric_value_matches_filter_option()
```

## 缓存版本

```php
pfl_get_facet_cache_version()
pfl_bump_facet_cache_version()
```

## AJAX 返回联动数据

```php
pfl_ajax_filter_products()
```

## 前端更新数量与禁用状态

```javascript
updateFacetState()
```

---

# 联动计数执行流程

```text
用户修改筛选条件
↓
JavaScript 防抖
↓
AJAX 提交当前筛选状态
↓
WordPress 查询产品结果
↓
按筛选组排除自身条件
↓
计算每个候选选项数量
↓
读取或写入 transient 缓存
↓
返回产品 HTML + facets
↓
前端更新产品结果、数量和禁用状态
↓
同步浏览器地址栏
```

---

# 安装与升级

## 新安装

1. 上传主题 ZIP；
2. 启用主题；
3. 进入“外观 → 产品主题演示数据”；
4. 导入或更新演示数据；
5. 进入“设置 → 固定链接”；
6. 点击“保存更改”。

## 从 v1.4.0 升级

主题目录名保持不变，可以直接覆盖旧文件。

升级后建议：

```text
1. 清除浏览器缓存
2. 清除页面缓存或对象缓存
3. 设置 → 固定链接 → 保存更改
4. 重新导入或更新演示数据
5. 测试多个筛选组合
```

---

# 推荐测试

## 测试一：品牌和电压联动

选择：

```text
电压：380V
```

观察不同品牌数量是否变化，数量为 0 的品牌是否禁用。

## 测试二：功能 AND 关系

依次选择：

```text
全自动
触屏控制
不锈钢机身
```

观察未选择功能旁边的数量是否按照“同时具备”继续缩小。

## 测试三：价格区间

选择品牌后观察各价格区间数量，再切换价格区间，确认 AJAX 产品数量一致。

## 测试四：分类专属属性

测试：

```text
真空包装机
输送设备
电机
传感器
```

确认不同分类只显示自己的筛选组，并且计数基于当前分类产品。

## 测试五：缓存

在 `wp-config.php` 中启用：

```php
define('WP_DEBUG', true);
```

管理员登录后重复访问同一筛选组合，观察：

```text
实时计算
缓存命中
```

状态变化。

---

# 技术说明

## 为什么排除当前筛选组

假设已经选择：

```text
品牌：华东机械
电压：380V
```

计算品牌数量时，如果继续保留“华东机械”条件，那么其他品牌都会变成 0。

因此标准 Faceted Search 通常采用：

```text
计算品牌
→ 保留电压等其他组
→ 暂时排除品牌组
```

这样用户才能看到切换或追加品牌后的真实候选数量。

## 为什么已选择的零结果项不禁用

当前组合可能因为其他条件变化而暂时变成 0。

如果把已选择项直接禁用，用户可能无法取消它。

因此规则是：

```text
count = 0 且未选中
→ 禁用

count = 0 但已选中
→ 保持可操作
```

---

# 后续方向

## v1.6.0

建议进入：

```text
筛选器折叠与移动端抽屉
筛选组“更多”展开
已选项置顶
产品对比
收藏状态
```

## v1.7.0

建议进入：

```text
筛选索引表
批量重建索引
大数据量性能测试
查询日志与管理面板
```

## v2.0.0

将产品内容类型、属性和筛选查询迁移到独立插件，主题只保留模板和视觉展示。

---

# 版本记录

## v1.5.0

- 增加筛选项联动数量；
- 增加 taxonomy IN 组候选数量；
- 增加 taxonomy AND 组追加条件数量；
- 增加数值区间候选数量；
- 增加零结果选项禁用；
- AJAX 响应增加 facets；
- 前端动态更新数量和 disabled 状态；
- 增加 Transient 筛选计数缓存；
- 增加缓存版本失效机制；
- 产品和 term 变化后自动刷新缓存版本；
- 增加管理员性能调试提示；
- 保持动态属性、分页、历史记录与 SEO 兼容。

## v1.4.0

- 产品分类内容管理；
- Canonical 和 Robots；
- Product、BreadcrumbList、CollectionPage 结构化数据。

## v1.3.0

- 动态产品属性；
- 分类专属筛选方案；
- 筛选配置继承。

## v1.2.0

- AJAX 无刷新筛选；
- AJAX 分页；
- 浏览器历史记录。

## v1.1.0

- 筛选体验和查询优化。

## v1.0.0

- 产品分类导航与多条件筛选基础版本。

---

## License

GPL-2.0-or-later
