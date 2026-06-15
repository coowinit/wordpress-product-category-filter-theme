# WordPress 产品分类导航与多条件筛选主题

> 一套用于学习和研究 WordPress 产品目录系统的经典主题，完整演示自定义文章类型、多层级分类导航、多条件筛选、主查询修改与模板拆分。

![Version](https://img.shields.io/badge/version-v1.0.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目简介

本项目是一套面向 WordPress 经典主题开发的产品分类与筛选学习主题。

它不是一个只展示静态样式的页面，而是将以下技术组合成一套可以直接安装、运行和调试的完整案例：

- 产品自定义文章类型
- 多层级产品分类法
- 产品品牌、电压、功能特点分类法
- 产品价格、功率、型号自定义字段
- 产品分类逐级导航
- 多条件组合筛选
- GET 查询参数
- `pre_get_posts`
- `tax_query`
- `meta_query`
- 价格排序
- 筛选条件分页保留
- 模板层级与模板组件拆分
- 后台一键导入演示数据

项目当前版本为：

```text
v1.0.0
```

本版本已经完成基础功能测试，可作为后续继续开发 AJAX 筛选、排序、产品属性系统和插件化架构的基础版本。

---

## 项目目标

本主题重点解决两个常见问题。

### 1. 多层级产品分类导航

例如：

```text
工业设备
└── 包装设备
    └── 真空包装机
```

访问不同分类页面时，主题会：

- 始终显示一级分类；
- 保留当前分类的所有上级导航；
- 显示当前路径下一层的直接子分类；
- 当前分类没有下级时停止继续展开；
- 对当前分类路径进行高亮。

### 2. 产品多条件筛选

例如：

```text
品牌：华东机械
电压：380V
功能：全自动 + 触屏控制
价格：1 万～3 万
功率：3～5kW
```

筛选条件通过 GET 参数提交，并由 WordPress 主查询处理。

---

## 页面效果结构

产品归档页和产品分类页采用以下结构：

```text
页面标题与说明
↓
多层级产品分类导航
↓
多条件筛选表单
↓
当前匹配产品数量
↓
产品卡片列表
↓
分页
```

分类导航与多条件筛选虽然视觉形式相似，但它们的职责不同：

```text
分类导航
负责决定当前产品目录范围

多条件筛选
负责在当前目录范围内进一步缩小结果
```

---

## 核心数据结构

### 产品文章类型

```text
post_type：product
```

每个产品是一条独立的 WordPress 内容记录。

支持：

- 标题
- 正文
- 摘要
- 特色图片
- 产品分类
- 产品品牌
- 产品电压
- 功能特点
- 产品型号
- 产品价格
- 产品功率

---

### 产品层级分类

```text
taxonomy：product_category
```

该分类法启用了层级关系：

```php
'hierarchical' => true
```

示例：

```text
工业设备
├── 包装设备
│   ├── 真空包装机
│   ├── 自动封口机
│   └── 热收缩机
├── 输送设备
└── 清洗设备
```

---

### 产品筛选分类法

```text
product_brand
product_voltage
product_feature
```

用途分别为：

| 分类法 | 用途 |
|---|---|
| `product_brand` | 产品品牌 |
| `product_voltage` | 产品电压 |
| `product_feature` | 功能特点 |

这些属性属于离散选项，适合使用 taxonomy 保存和筛选。

---

### 产品自定义字段

```text
product_model
product_price
product_power
```

| 字段 | 用途 |
|---|---|
| `product_model` | 产品型号 |
| `product_price` | 产品价格 |
| `product_power` | 产品功率 |

价格和功率使用纯数字保存，方便进行：

- 小于
- 大于
- 区间
- 数值排序

---

## 技术架构

```text
product 自定义文章类型
│
├── product_category
│   └── 多层级产品分类导航
│
├── product_brand
├── product_voltage
├── product_feature
│   └── 多条件 taxonomy 筛选
│
├── product_price
├── product_power
└── product_model
    └── 产品自定义字段
```

---

## 主题文件结构

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
│       ├── category-navigation.php
│       ├── filter-form.php
│       └── card.php
│
└── assets/
    ├── css/
    │   └── product.css
    └── js/
        └── product-filter.js
```

---

## 文件职责说明

| 文件 | 主要职责 |
|---|---|
| `functions.php` | 注册内容类型、分类法、字段、查询逻辑和演示数据 |
| `front-page.php` | 学习主题首页 |
| `archive-product.php` | 全部产品归档页 |
| `taxonomy-product_category.php` | 产品分类归档页 |
| `single-product.php` | 单个产品详情页 |
| `index.php` | 主题最终兜底模板 |
| `category-navigation.php` | 输出多层级产品分类导航 |
| `filter-form.php` | 输出产品多条件筛选表单 |
| `card.php` | 输出单个产品卡片 |
| `product.css` | 产品导航、筛选器、卡片和分页样式 |
| `product-filter.js` | 已选条件标签与前端交互 |

---

## 安装方法

### 方式一：后台上传主题

进入 WordPress 后台：

```text
外观
→ 主题
→ 安装主题
→ 上传主题
```

上传主题 ZIP 压缩包并启用。

### 方式二：手动安装

将主题目录复制到：

```text
wp-content/themes/
```

然后进入 WordPress 后台启用主题。

---

## 导入演示数据

启用主题后，进入：

```text
外观
→ 产品主题演示数据
```

点击：

```text
一键导入演示数据
```

导入器会自动创建：

- 多层级产品分类；
- 产品品牌；
- 产品电压；
- 功能特点；
- 12 条演示产品；
- 产品型号；
- 产品价格；
- 产品功率。

导入器会检查已有数据，避免重复创建同名分类和演示产品。

---

## 刷新固定链接

启用主题或导入数据后，建议进入：

```text
设置
→ 固定链接
```

直接点击一次：

```text
保存更改
```

这样可以刷新产品归档与分类法重写规则。

---

## 主要访问地址

### 全部产品归档

```text
/products/
```

### 产品分类页

```text
/product-category/分类别名/
```

启用层级固定链接后，地址可能类似：

```text
/product-category/industrial-equipment/packaging-equipment/
```

### 产品详情页

```text
/products/产品别名/
```

---

## 演示数据结构

### 产品分类

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

### 产品品牌

```text
华东机械
蓝海设备
启航工业
新锐科技
```

### 产品电压

```text
220V
380V
定制电压
```

### 功能特点

```text
全自动
触屏控制
不锈钢机身
支持定制
节能型
```

---

## 多层级分类导航原理

核心函数位于：

```php
pfl_get_category_navigation_levels()
```

主要流程：

```text
获取当前分类对象
↓
获取当前分类的所有祖先
↓
反转祖先顺序
↓
生成当前完整分类路径
↓
查询所有一级分类
↓
沿当前路径逐级获取直接子分类
↓
返回每一级导航数据
```

核心 WordPress 函数：

```php
get_queried_object();
get_ancestors();
get_terms();
get_term_link();
```

---

## 分类导航页面行为

### 访问全部产品页

```text
一级分类：
工业设备
商用设备
零部件
```

### 访问“工业设备”

```text
一级分类：
工业设备
商用设备
零部件

二级分类：
包装设备
输送设备
清洗设备
```

### 访问“包装设备”

```text
一级分类：
工业设备
商用设备
零部件

二级分类：
包装设备
输送设备
清洗设备

三级分类：
真空包装机
自动封口机
热收缩机
```

### 访问没有下级的分类

主题不会继续输出不存在的下一层导航。

---

## 多条件筛选原理

筛选表单位于：

```text
template-parts/product/filter-form.php
```

表单使用：

```html
<form method="get">
```

筛选后 URL 可能类似：

```text
/products/
?brand[]=huadong
&voltage[]=380v
&feature[]=automatic
&price_range=10000-30000
&power_range=3-5
```

这种实现方式具有以下优点：

- 筛选状态可以复制和分享；
- 页面刷新后条件不会丢失；
- 浏览器前进和后退可以正常工作；
- 不依赖 JavaScript 也能完成筛选；
- 容易观察 WordPress 查询过程；
- 方便后续升级 AJAX。

---

## 主查询修改

主题没有在产品模板中重新创建第二个 `WP_Query`。

而是使用：

```php
add_action(
    'pre_get_posts',
    'pfl_filter_product_main_query'
);
```

核心函数：

```php
pfl_filter_product_main_query()
```

它只处理：

```text
产品归档页
产品分类归档页
```

并且通过以下条件避免影响后台和其他查询：

```php
is_admin()
$query->is_main_query()
```

---

## taxonomy 筛选

品牌、电压和功能特点使用：

```php
tax_query
```

不同筛选组之间使用：

```text
AND
```

例如：

```text
品牌
AND
电压
AND
功能特点
```

同一组内的逻辑：

| 筛选组 | 运算关系 |
|---|---|
| 品牌 | `IN` |
| 电压 | `IN` |
| 功能特点 | `AND` |

因此：

```text
品牌 A 或品牌 B
AND
380V
AND
同时具备“全自动”和“触屏控制”
```

---

## 数值筛选

价格和功率使用：

```php
meta_query
```

支持：

```text
小于
区间
大于或等于
```

例如价格区间：

```php
[
    'key'     => 'product_price',
    'value'   => [10000, 30000],
    'type'    => 'NUMERIC',
    'compare' => 'BETWEEN',
]
```

---

## 产品排序

当前版本支持：

```text
最新发布
价格从低到高
价格从高到低
标题排序
```

价格排序使用：

```php
meta_key
meta_value_num
```

确保价格按照数字而不是字符串排序。

---

## 分页与筛选参数

筛选后进入第二页时，主题会保留当前筛选条件。

核心函数：

```php
pfl_get_pagination_filter_args()
```

分页使用：

```php
paginate_links()
```

并通过：

```php
'add_args'
```

把筛选参数继续附加到分页链接。

---

## 前端 JavaScript 的职责

文件：

```text
assets/js/product-filter.js
```

当前 JavaScript 只负责界面交互：

- 更新选中按钮样式；
- 显示已选条件标签；
- 删除单个已选条件；
- 清空全部条件。

真正的产品查询仍然由 PHP 和 WordPress 完成。

```text
前端选择条件
↓
提交 GET 参数
↓
PHP 清理参数
↓
pre_get_posts
↓
tax_query / meta_query
↓
输出产品结果
```

---

## 产品主循环

产品归档与分类模板继续使用 WordPress 原生主循环：

```php
if (have_posts()) {
    while (have_posts()) {
        the_post();
    }
}
```

这样可以避免：

- 重复数据库查询；
- 分页错误；
- 全局 `$post` 混乱；
- 分类页面查询条件丢失；
- SEO 分页地址与内容不一致。

---

## v1.0.0 已完成功能

- [x] 产品自定义文章类型
- [x] 产品层级分类法
- [x] 产品品牌分类法
- [x] 产品电压分类法
- [x] 功能特点分类法
- [x] 产品型号字段
- [x] 产品价格字段
- [x] 产品功率字段
- [x] 产品参数后台元框
- [x] 多层级分类导航
- [x] 当前分类路径高亮
- [x] 多条件 GET 筛选
- [x] taxonomy 组合筛选
- [x] 数值区间筛选
- [x] 产品排序
- [x] 筛选参数分页保留
- [x] 已选条件标签
- [x] 产品卡片
- [x] 产品详情模板
- [x] 响应式布局
- [x] 演示数据一键导入
- [x] 产品归档分页样式修复
- [x] `fieldset` 筛选选项横向布局修复

---

## 当前版本定位

v1.0.0 是一个以学习和研究为目标的稳定基础版本。

为了更清楚地理解 WordPress 原生请求过程，本版本暂未加入 AJAX 无刷新筛选。

当前流程是：

```text
选择筛选条件
↓
点击查看筛选结果
↓
提交 GET 表单
↓
服务器重新查询
↓
返回筛选结果
```

---

## 后续优化方向

### v1.1.0：筛选体验增强

计划增加：

- 条件变化后自动提交；
- 当前条件未应用提示；
- 筛选加载状态；
- 产品数量实时提示；
- 移动端筛选折叠面板；
- 更清晰的重置与返回全部产品按钮。

### v1.2.0：AJAX 无刷新筛选

计划增加：

- AJAX 更新产品列表；
- AJAX 更新匹配数量；
- AJAX 更新分页；
- 浏览器地址栏同步；
- `history.pushState()`；
- 前进和后退恢复筛选状态；
- 保留无 JavaScript 降级方案。

### v1.3.0：产品属性系统

计划增加：

- 产品材质；
- 应用行业；
- 产品用途；
- 最低价和最高价输入；
- 产品标签；
- 产品参数分组；
- 分类图片；
- 分类排序。

### v2.0.0：业务插件化

计划将以下业务功能从主题迁移到独立插件：

- 产品文章类型；
- 产品分类法；
- 产品属性分类法；
- 产品字段；
- 筛选查询；
- 演示数据工具。

最终结构：

```text
插件负责数据和业务逻辑
主题负责页面结构和视觉展示
```

这样更换主题时，产品数据结构不会消失。

---

## 推荐学习顺序

### 第一步：理解数据注册

查看：

```php
pfl_register_product_post_type()
pfl_register_product_taxonomies()
pfl_register_product_meta()
```

### 第二步：理解分类导航

查看：

```php
pfl_get_category_navigation_levels()
```

以及：

```text
template-parts/product/category-navigation.php
```

### 第三步：理解筛选表单

查看：

```text
template-parts/product/filter-form.php
```

观察：

```text
input name
input value
GET 参数
选中状态
```

### 第四步：理解主查询

查看：

```php
pfl_filter_product_main_query()
```

重点研究：

```text
pre_get_posts
tax_query
meta_query
orderby
```

### 第五步：理解模板组合

查看：

```text
archive-product.php
taxonomy-product_category.php
```

理解：

```text
模板负责页面结构
组件负责局部 HTML
functions.php 负责数据和查询
```

---

## 常见问题

### 产品归档页面出现 404

进入：

```text
设置 → 固定链接
```

点击一次“保存更改”。

### 分类导航没有显示子分类

请检查：

- 当前分类是否真的存在子分类；
- `product_category` 是否启用了 `hierarchical`；
- 分类父子关系是否设置正确；
- 当前访问的是不是产品分类归档页。

### 筛选选中后产品没有变化

当前版本需要点击：

```text
查看筛选结果
```

条件才会通过 GET 表单提交。

### 功能特点多选后结果较少

当前功能特点使用：

```text
AND
```

表示产品必须同时具备所有已选功能。

如需改成“满足任意一个功能”，可将操作符改成：

```php
'operator' => 'IN'
```

### 价格排序不正确

请确保 `product_price` 保存的是纯数字：

```text
26800
```

不要保存为：

```text
￥26,800 元
```

---

## 开发环境建议

推荐环境：

```text
WordPress 6.x
PHP 7.4 或更高版本
MySQL 5.7+ / MariaDB 10.4+
Apache 或 Nginx
```

本地开发可以使用：

```text
XAMPP
Local
Docker
Laravel Herd
```

---

## 注意事项

本项目当前将内容类型、分类法、产品字段和业务查询集中写在主题的 `functions.php` 中，这是为了便于学习和集中阅读。

在正式商业项目中，更推荐：

```text
自定义文章类型和分类法 → 插件
模板和样式 → 主题
```

---

## 版本记录

### v1.0.0

首个稳定学习版本。

主要内容：

- 完成产品自定义文章类型；
- 完成多层级产品分类；
- 完成品牌、电压、功能特点筛选；
- 完成价格和功率区间筛选；
- 完成分类导航逐级展开；
- 完成产品归档与分类归档；
- 完成产品列表、详情和分页；
- 完成演示数据一键导入；
- 修复筛选选项竖排问题；
- 修复产品分页列表样式问题；
- 优化分类下级指示图标。

---

## License

本项目采用 GPL-2.0-or-later 许可证。

你可以自由学习、修改和继续扩展本项目。
