# WordPress 产品分类导航与动态筛选主题

> 一套用于学习 WordPress 产品目录系统的经典主题，完整演示多层级分类导航、分类专属动态属性、AJAX 无刷新筛选、分类内容管理、Canonical、Robots 与结构化数据。

![Version](https://img.shields.io/badge/version-v1.4.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目简介

本项目是一套面向 WordPress 经典主题开发的产品目录学习主题。

它从基础的产品自定义文章类型开始，逐步实现：

- 多层级产品分类导航；
- 产品分类路径高亮；
- 分类专属筛选方案；
- 父分类筛选配置继承；
- 动态产品属性；
- AJAX 无刷新筛选；
- 普通 GET 降级；
- 浏览器历史记录同步；
- 分类图片与上下文内容；
- 产品归档 SEO 控制；
- Product、BreadcrumbList 与 CollectionPage 结构化数据。

当前版本：

```text
v1.4.0
```

v1.4.0 在 v1.3.0 动态产品属性系统的基础上，重点完善产品分类页面内容和基础 SEO 技术实现。

---

# v1.4.0 核心更新

## 产品分类内容管理

产品分类后台新增：

- 分类图片；
- 分类顶部内容；
- 分类底部内容；
- SEO 标题；
- Meta Description。

分类页面结构升级为：

```text
面包屑
↓
分类图片 + 分类标题 + 顶部内容
↓
多层级分类导航
↓
动态多条件筛选
↓
产品结果
↓
分类底部补充内容
```

## SEO 基础能力

新增：

- 产品分类 SEO 标题；
- 产品归档 Meta Description；
- 产品详情 Meta Description；
- 产品归档 Canonical；
- 产品分类 Canonical；
- 筛选结果页 `noindex, follow`；
- 筛选页 Canonical 指向干净归档地址；
- 常见 SEO 插件启用时自动停用主题内置 SEO 输出。

## 结构化数据

新增 JSON-LD：

```text
Product
BreadcrumbList
CollectionPage
ItemList
Offer
Brand
PropertyValue
```

其中：

- 产品详情页输出 `Product`；
- 产品归档与干净分类归档输出 `CollectionPage`；
- 产品相关页面输出 `BreadcrumbList`；
- 带筛选参数的结果页不输出 CollectionPage，避免结构化数据与 Canonical 页面内容不一致。

---

# 技术架构

```text
product 自定义文章类型
│
├── product_category
│   ├── 多层级分类导航
│   ├── 分类图片
│   ├── 分类顶部内容
│   ├── 分类底部内容
│   ├── SEO 标题
│   ├── Meta Description
│   └── 分类专属筛选方案
│
├── 产品属性 taxonomy
│   ├── 品牌
│   ├── 电压
│   ├── 功能
│   ├── 材质
│   ├── 应用行业
│   ├── 自动化程度
│   ├── 安装方式
│   ├── 输出类型
│   ├── 防护等级
│   ├── 包装方式
│   ├── 输送方式
│   └── 检测方式
│
├── 数值 post meta
│   ├── 价格
│   ├── 功率
│   ├── 转速
│   ├── 宽度
│   ├── 检测距离
│   └── 运行速度
│
└── 前端查询
    ├── 普通 GET
    ├── AJAX
    ├── tax_query
    ├── meta_query
    ├── 分页
    ├── 排序
    └── history.pushState
```

---

# 主题文件结构

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
├── screenshot.png
│
├── template-parts/
│   └── product/
│       ├── breadcrumbs.php
│       ├── category-hero.php
│       ├── category-bottom-content.php
│       ├── category-navigation.php
│       ├── filter-form.php
│       ├── card.php
│       ├── results.php
│       └── no-results.php
│
└── assets/
    ├── css/
    │   └── product.css
    ├── js/
    │   └── product-filter.js
    └── images/
        ├── category-industrial.jpg
        ├── category-commercial.jpg
        ├── category-parts.jpg
        └── category-packaging.jpg
```

---

# 文件职责

| 文件 | 主要职责 |
|---|---|
| `functions.php` | 注册产品类型、分类法、字段、筛选、AJAX、SEO 与演示数据 |
| `archive-product.php` | 全部产品归档页 |
| `taxonomy-product_category.php` | 产品分类页总结构 |
| `single-product.php` | 产品详情页 |
| `category-hero.php` | 分类图片、标题和顶部内容 |
| `category-bottom-content.php` | 分类结果下方补充内容 |
| `category-navigation.php` | 多层级产品分类导航 |
| `filter-form.php` | 配置驱动的动态筛选表单 |
| `results.php` | GET 与 AJAX 共用结果区 |
| `breadcrumbs.php` | 产品归档、分类与详情面包屑 |
| `product-filter.js` | AJAX、分页、URL 和浏览器历史记录 |
| `product.css` | 分类、筛选、结果与内容区域样式 |

---

# 安装方法

## WordPress 后台安装

进入：

```text
外观
→ 主题
→ 安装主题
→ 上传主题
```

上传主题 ZIP 文件并启用。

## 手动安装

将主题目录复制到：

```text
wp-content/themes/
```

然后在 WordPress 后台启用主题。

---

# 从 v1.3.0 升级

主题目录名保持不变：

```text
wp-product-navigation-filter-lab
```

可以直接覆盖旧版本文件。

升级后依次执行：

```text
1. 清除浏览器缓存和站点缓存
2. 外观 → 产品主题演示数据
3. 点击“导入或更新 v1.4.0 演示数据”
4. 设置 → 固定链接
5. 点击“保存更改”
```

演示数据更新会：

- 保留已有同名演示产品；
- 更新动态属性；
- 写入分类筛选方案；
- 写入分类顶部和底部内容；
- 写入分类 SEO 标题和 Meta Description；
- 导入分类演示图片。

---

# 主要访问地址

## 产品归档

```text
/products/
```

## 产品分类

```text
/product-category/分类别名/
```

## 产品详情

```text
/products/产品别名/
```

---

# 产品分类内容字段

## 分类图片

字段：

```text
pfl_category_image_id
```

用途：

- 分类页头部图片；
- CollectionPage 的 `primaryImageOfPage`；
- 分类后台列表预览。

## 分类顶部内容

字段：

```text
pfl_category_top_content
```

显示位置：

```text
分类标题下方
```

适合：

- 分类简介；
- 产品范围说明；
- 主要应用场景；
- 筛选使用提示。

## 分类底部内容

字段：

```text
pfl_category_bottom_content
```

显示位置：

```text
产品结果和分页之后
```

适合：

- 选型指南；
- 参数解释；
- 应用说明；
- 常见问题；
- 分类补充内容。

## SEO 标题

字段：

```text
pfl_category_seo_title
```

留空时继续使用 WordPress 默认文档标题。

## Meta Description

字段：

```text
pfl_category_meta_description
```

未填写时，主题依次尝试：

```text
分类顶部内容
↓
WordPress 分类描述
↓
自动生成的分类描述
```

---

# Canonical 逻辑

## 干净产品归档

```text
/products/
```

Canonical 指向自身。

## 干净产品分类

```text
/product-category/industrial-equipment/
```

Canonical 指向自身。

## 带筛选参数的页面

例如：

```text
/products/?brand[]=huadong&voltage[]=380v
```

主题将：

```text
Robots：noindex, follow
Canonical：/products/
```

分类筛选页同样指向当前分类的干净地址。

## 干净分页

未附加筛选参数时，分页 Canonical 保留当前页码。

---

# Robots 逻辑

主题通过：

```php
wp_robots
```

检查以下参数：

- 所有动态筛选 Schema 参数；
- 排序参数 `sort`。

只要存在有效筛选或排序条件，就增加：

```text
noindex
follow
```

这样可以减少大量参数组合页面被搜索引擎重复索引。

---

# 内置 SEO 与第三方 SEO 插件

主题内置 SEO 主要用于学习原理。

检测到以下常见 SEO 插件常量时，主题会自动停用自身的 Meta、Canonical 和 JSON-LD 输出：

```text
Yoast SEO
Rank Math
All in One SEO
SEOPress
```

也可以通过过滤器手动控制：

```php
add_filter(
    'pfl_enable_builtin_seo',
    '__return_false'
);
```

正式网站已经使用专业 SEO 插件时，建议由插件统一管理 SEO 输出。

---

# Product 结构化数据

产品详情页输出：

```text
Product
```

可包含：

- 产品名称；
- 产品地址；
- 产品描述；
- 产品图片；
- 型号；
- 品牌；
- 产品分类；
- 价格和 CNY 币种；
- 电压；
- 材质；
- 自动化程度；
- 防护等级；
- 功率。

示意：

```json
{
  "@type": "Product",
  "name": "双室真空包装机",
  "sku": "VP-600D",
  "brand": {
    "@type": "Brand",
    "name": "华东机械"
  },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "CNY",
    "price": "46800.00"
  }
}
```

当前版本是教学型基础实现，不代替完整电商库存、配送、退货和商家政策数据。

---

# BreadcrumbList 结构化数据

以下页面输出面包屑 JSON-LD：

- 全部产品归档；
- 产品分类；
- 产品详情。

产品详情路径示例：

```text
首页
→ 产品中心
→ 工业设备
→ 包装设备
→ 真空包装机
→ 双室真空包装机
```

前端可见面包屑和 JSON-LD 使用同一份数据来源。

---

# CollectionPage 结构化数据

干净的产品归档和分类归档输出：

```text
CollectionPage
└── ItemList
```

ItemList 使用当前分页显示的产品。

带筛选参数的页面不会输出 CollectionPage，原因是：

```text
筛选页面内容
≠
Canonical 指向的干净归档内容
```

这样可以避免结构化数据与 Canonical 页面不一致。

---

# 多层级分类导航

导航实现继续保留：

- 一级分类始终显示；
- 当前路径逐级保留；
- 当前分类显示直接子分类；
- 祖先分类和当前分类使用不同状态；
- 没有下级时停止展开；
- 一次读取分类并构建父子索引。

核心函数：

```php
pfl_get_product_category_index()
pfl_get_category_navigation_levels()
pfl_get_current_product_category_path()
```

---

# 动态筛选系统

v1.4.0 继续使用 v1.3.0 的统一 Schema：

```php
pfl_get_product_filter_schema()
```

同一份配置驱动：

- 前端筛选组；
- GET 参数读取；
- AJAX 参数读取；
- 参数白名单；
- `tax_query`；
- `meta_query`；
- 已选条件；
- 分页参数；
- 浏览器历史记录。

---

# 分类专属筛选方案

产品分类后台支持：

```text
继承父级或全局默认方案
使用当前分类专属方案
```

专属方案支持：

- 勾选筛选组；
- 拖动排序；
- 子分类继承；
- 自动隐藏当前范围没有有效数据的筛选组。

例如：

```text
包装设备：
品牌、电压、自动化程度、包装方式、材质、功能、价格、功率

电机：
品牌、电压、安装方式、防护等级、功率、转速、价格

传感器：
品牌、电压、检测方式、输出类型、防护等级、检测距离、价格
```

---

# AJAX 与普通 GET

## JavaScript 可用

```text
选择条件
↓
AJAX 请求
↓
更新产品数量
↓
更新卡片
↓
更新分页
↓
更新地址栏
```

## JavaScript 不可用或请求失败

```text
普通 GET 表单
↓
WordPress 主查询
↓
完整页面返回
```

这种方式属于渐进增强。

---

# 演示数据

v1.4.0 演示数据包括：

## 产品分类

```text
工业设备
├── 包装设备
│   ├── 真空包装机
│   ├── 自动封口机
│   └── 热收缩机
├── 输送设备
└── 清洗设备

商用设备
├── 厨房设备
└── 商用清洁设备

零部件
├── 电机
└── 传感器
```

## 分类内容示例

重点分类会自动写入：

- 分类图片；
- 顶部简介；
- 底部选型说明；
- SEO 标题；
- Meta Description。

## 产品数据

包含包装设备、输送设备、电机、传感器等多种演示产品，用于测试：

- 动态筛选；
- 分类继承；
- 数值范围；
- Product JSON-LD；
- 分类结构化数据。

---

# 推荐测试清单

## 分类内容

- [ ] 分类图片正常显示；
- [ ] 顶部内容正常显示；
- [ ] 底部内容正常显示；
- [ ] 没有自定义顶部内容时回退到分类描述；
- [ ] 后台可选择和移除分类图片。

## SEO

- [ ] 分类 SEO 标题生效；
- [ ] Meta Description 正常输出；
- [ ] 干净分类页 Canonical 指向自身；
- [ ] 筛选页输出 `noindex, follow`；
- [ ] 筛选页 Canonical 指向干净分类页；
- [ ] 使用 SEO 插件时主题内置输出停止。

## 结构化数据

- [ ] 产品详情输出 Product；
- [ ] 产品详情输出 BreadcrumbList；
- [ ] 干净归档输出 CollectionPage；
- [ ] 筛选归档不输出 CollectionPage；
- [ ] JSON-LD 是有效 JSON。

## 原有功能回归

- [ ] 分类层级导航正常；
- [ ] 分类专属筛选方案正常；
- [ ] AJAX 筛选正常；
- [ ] AJAX 分页正常；
- [ ] 排序正常；
- [ ] 浏览器前进和后退正常；
- [ ] 普通 GET 降级正常。

---

# v1.4.0 已完成功能

- [x] 分类图片 term meta
- [x] WordPress 媒体库图片选择器
- [x] 分类顶部内容
- [x] 分类底部内容
- [x] 分类 SEO 标题
- [x] 分类 Meta Description
- [x] 分类后台 SEO 状态列
- [x] 产品详情 Meta Description
- [x] 产品归档 Canonical
- [x] 产品分类 Canonical
- [x] 筛选结果 noindex
- [x] 筛选结果 Canonical 清理
- [x] Product JSON-LD
- [x] BreadcrumbList JSON-LD
- [x] CollectionPage JSON-LD
- [x] ItemList JSON-LD
- [x] 产品详情可见面包屑
- [x] 常见 SEO 插件冲突规避
- [x] 分类演示图片导入
- [x] 分类内容演示数据
- [x] 保留动态属性与 AJAX 筛选

---

# 后续版本规划

## v1.5.0：筛选计数与性能优化

计划增加：

- 当前条件上下文的实时选项数量；
- 不可用筛选项禁用；
- 选项数量 AJAX 联动；
- 查询结果缓存；
- term 计数缓存；
- AJAX 响应性能分析；
- 数据库查询次数展示；
- 缓存失效策略。

## v1.6.0：产品展示与转化增强

计划增加：

- 产品图库；
- 参数分组；
- 相关产品；
- 产品对比；
- 收藏或询盘入口；
- 产品打印页面；
- 更完整的产品结构化数据。

## v2.0.0：业务插件化

计划将以下功能迁移到独立插件：

```text
产品文章类型
产品分类法
产品属性
产品字段
筛选 Schema
AJAX 查询
分类内容字段
SEO 业务规则
演示数据工具
```

主题只保留：

```text
模板
HTML
CSS
JavaScript
展示组件
```

---

# 注意事项

本项目为了便于集中学习，按照当前开发目标将产品类型、分类法、字段、筛选和 SEO 逻辑写在主题 `functions.php` 中。

正式商业项目更推荐：

```text
插件负责数据与业务逻辑
主题负责模板与视觉展示
专业 SEO 插件负责站点 SEO
```

---

# 版本记录

## v1.4.0

- 增加分类图片、顶部内容和底部内容；
- 增加分类 SEO 标题和 Meta Description；
- 增加产品详情 Meta Description；
- 增加产品归档和分类归档 Canonical；
- 筛选和排序页面增加 `noindex, follow`；
- 筛选页面 Canonical 指向干净归档；
- 增加 Product、BreadcrumbList 和 CollectionPage JSON-LD；
- 产品详情页增加可见面包屑；
- 增加 SEO 插件冲突规避；
- 增加分类图片及 SEO 演示数据。

## v1.3.0

- 动态产品属性系统；
- 分类专属筛选方案；
- 父级筛选配置继承；
- 分类后台筛选组勾选与排序。

## v1.2.0

- AJAX 无刷新筛选；
- AJAX 分页；
- 地址栏和浏览器历史记录同步；
- 普通 GET 降级。

## v1.1.0

- 筛选体验优化；
- 面包屑与返回上一级；
- 查询安全和后台产品管理增强。

## v1.0.0

- 产品自定义文章类型；
- 多层级分类导航；
- GET 多条件筛选；
- `pre_get_posts`、`tax_query` 和 `meta_query`。

---

# License

本项目采用 GPL-2.0-or-later 许可证。

你可以自由学习、修改和继续扩展本项目。
