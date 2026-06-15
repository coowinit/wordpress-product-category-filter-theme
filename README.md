# WordPress 产品分类导航与动态属性筛选主题

> 一套用于学习 WordPress 产品目录系统的经典主题，完整演示多层级分类导航、分类专属筛选方案、动态产品属性、AJAX 无刷新查询与父级配置继承。

![Version](https://img.shields.io/badge/version-v1.3.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目简介

本项目是一套面向 WordPress 经典主题开发的产品目录学习主题。

v1.3.0 在 v1.2.0 AJAX 无刷新筛选的基础上，将固定筛选器升级为**配置驱动的动态产品属性系统**：

- 不同产品分类可以显示不同筛选维度；
- 子分类可以继承父分类的筛选方案；
- 分类后台可以切换“继承”或“专属方案”；
- 筛选组支持勾选与拖动排序；
- GET、AJAX、分页、历史记录和参数验证共用同一份 Schema；
- 当前分类中没有有效数据的筛选组会自动隐藏；
- taxonomy 选项会显示当前分类范围内的基础产品数量。

项目当前版本：

```text
v1.3.0
```

---

## 核心功能

### 产品内容结构

- `product` 产品自定义文章类型
- `product_category` 多层级产品分类
- 产品品牌、电压、功能、材质、应用行业等属性分类法
- 价格、功率、转速、宽度、检测距离和运行速度等数值字段

### 分类导航

- 多层级逐级展开
- 当前路径保留
- 祖先分类与当前分类高亮
- 面包屑
- 返回上一级
- 一次加载分类并建立父子索引

### 动态筛选

- 分类专属筛选组
- 父级筛选方案继承
- 后台勾选筛选组
- 后台拖拽排序
- 自动隐藏无可用数据的筛选组
- taxonomy 选项显示当前分类基础数量
- 多选、范围、排序组合查询

### AJAX 与降级

- AJAX 无刷新更新产品列表
- AJAX 分页和排序
- 地址栏同步
- 浏览器前进、后退
- 刷新后恢复条件
- 请求失败自动降级为普通 GET
- 禁用 JavaScript 时仍可正常筛选

---

## 新增属性

### 离散属性 taxonomy

```text
product_brand             产品品牌
product_voltage           产品电压
product_feature           功能特点
product_material          产品材质
product_application       应用行业
product_automation        自动化程度
product_installation      安装方式
product_output_type       输出类型
product_protection        防护等级
product_packaging_type    包装方式
product_conveyor_type     输送方式
product_detection_type    检测方式
```

### 数值字段 post meta

```text
product_model                 产品型号
product_price                 产品价格
product_power                 产品功率
product_rpm                   电机转速
product_width                 设备宽度
product_detection_distance    检测距离
product_speed                 运行速度
```

---

## 动态筛选示例

### 全部产品

```text
品牌
电压
功能特点
材质
应用行业
价格
功率
```

### 包装设备

```text
品牌
电压
自动化程度
包装方式
材质
功能特点
价格
功率
```

“真空包装机”“自动封口机”“热收缩机”默认继承该方案。

### 输送设备

```text
品牌
电压
输送方式
安装方式
材质
设备宽度
运行速度
功率
价格
```

### 电机

```text
品牌
电压
安装方式
防护等级
功率
转速
价格
```

### 传感器

```text
品牌
电压
检测方式
输出类型
防护等级
检测距离
价格
```

---

## 统一筛选 Schema

核心函数：

```php
pfl_get_product_filter_schema()
```

每个筛选组统一声明：

```php
'brand' => [
    'label'    => '产品品牌',
    'type'     => 'taxonomy',
    'taxonomy' => 'product_brand',
    'operator' => 'IN',
    'input'    => 'checkbox',
]
```

数值范围示例：

```php
'rpm_range' => [
    'label'    => '转速范围',
    'type'     => 'range',
    'meta_key' => 'product_rpm',
    'options'  => [...],
]
```

同一份 Schema 同时用于：

```text
前端筛选表单
后台分类筛选设置
GET 参数白名单
AJAX 参数验证
tax_query
meta_query
已选条件统计
分页参数保留
```

---

## 分类筛选方案继承

分类筛选配置保存于 term meta：

```text
pfl_filter_mode
pfl_filter_groups
```

查找顺序：

```text
当前分类专属方案
↓
最近的父分类专属方案
↓
更高层祖先方案
↓
全局默认方案
```

核心函数：

```php
pfl_resolve_filter_profile()
pfl_get_active_filter_keys()
```

前端会显示当前方案来源，例如：

```text
当前分类专属方案
继承自“包装设备”
继承全局默认方案
```

---

## 后台配置方法

进入：

```text
产品中心
→ 产品分类
→ 编辑某个分类
```

可以选择：

```text
继承父分类或全局默认方案
使用当前分类专属方案
```

选择专属方案后：

1. 勾选需要显示的筛选组；
2. 拖动筛选组调整顺序；
3. 更新分类；
4. 前台分类页面自动按新配置显示。

产品分类列表还会显示“继承”或当前专属筛选组摘要。

---

## 当前分类有效选项

taxonomy 选项不是简单输出全站全部 term。

分类页会先取得当前分类及其子分类范围中的产品，然后统计这些产品实际使用的属性：

```text
当前产品分类
↓
取得范围内产品 ID
↓
读取产品关联的属性 term
↓
统计每个属性的基础产品数量
↓
只输出有数据的选项
```

核心函数：

```php
pfl_get_context_product_ids()
pfl_get_filter_term_options()
pfl_range_filter_has_values()
```

当前版本显示的是**当前分类基础数量**，不是随着其他筛选条件实时变化的联动数量。

---

## 查询逻辑

### taxonomy 属性

使用：

```php
tax_query
```

品牌、电压等组内使用 `IN`，功能特点继续使用 `AND`。

### 数值范围

使用：

```php
meta_query
```

支持：

```text
<
BETWEEN
>=
```

### 分类上下文

产品分类归档本身负责限定产品目录范围，动态属性继续追加到该主查询中。

普通 GET 和 AJAX 共用：

```php
pfl_get_product_query_parts()
pfl_apply_product_query_parts()
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
│       ├── breadcrumbs.php
│       ├── category-navigation.php
│       ├── filter-form.php
│       ├── results.php
│       ├── no-results.php
│       └── card.php
│
└── assets/
    ├── css/
    │   └── product.css
    └── js/
        └── product-filter.js
```

---

## 安装与升级

### 新安装

1. 上传主题 ZIP；
2. 启用主题；
3. 进入“外观 → 产品主题演示数据”；
4. 点击“导入或更新 v1.3.0 演示数据”；
5. 进入“设置 → 固定链接”；
6. 点击一次“保存更改”；
7. 打开 `/products/`。

### 从 v1.2.0 升级

主题目录名称保持不变，可以直接覆盖。

升级后务必再次运行：

```text
外观 → 产品主题演示数据
→ 导入或更新 v1.3.0 演示数据
```

这一步会：

- 为已有演示产品补充新属性；
- 写入转速、宽度、检测距离等数值；
- 新增缺少的演示产品；
- 写入分类专属筛选方案；
- 已有同名产品不会重复创建。

随后清除浏览器与站点缓存，并重新保存固定链接。

---

## 测试建议

### 包装设备继承

1. 打开“包装设备”；
2. 确认显示包装方式和自动化程度；
3. 打开“真空包装机”；
4. 确认显示“继承自包装设备”。

### 分类差异

分别访问：

```text
输送设备
电机
传感器
```

确认筛选组和顺序各不相同。

### 后台专属配置

1. 编辑某个产品分类；
2. 选择“专属方案”；
3. 勾选并拖动筛选组；
4. 更新后刷新前台分类页。

### AJAX 兼容

测试：

- 条件自动更新；
- 分页；
- 排序；
- 地址栏；
- 前进和后退；
- 刷新恢复；
- JavaScript 禁用后的普通 GET。

---

## v1.3.0 已完成功能

- [x] 统一筛选 Schema
- [x] 12 个离散属性分类法
- [x] 7 个产品数值/文本字段
- [x] 不同分类显示不同筛选组
- [x] 父分类筛选方案继承
- [x] 分类后台继承/专属模式
- [x] 后台筛选组勾选
- [x] 后台筛选组拖动排序
- [x] 分类列表显示筛选方案摘要
- [x] 自动隐藏无有效选项的 taxonomy 组
- [x] 自动隐藏没有数值数据的范围组
- [x] 当前分类范围基础选项数量
- [x] GET 与 AJAX 动态参数白名单
- [x] 动态 `tax_query` 与 `meta_query`
- [x] AJAX 分页和历史记录兼容
- [x] 演示数据升级与已有产品更新
- [x] 产品详情显示扩展属性

---

## 后续方向

### v1.4.0

SEO 与分类内容增强：

- 分类特色图片
- 分类顶部与底部内容
- 产品面包屑 Schema
- Product Schema
- 筛选参数 canonical
- 参数页面索引控制

### v1.5.0

联动计数和性能增强：

- 其他条件作用下的实时筛选数量
- 无结果选项禁用
- 属性统计缓存
- 大数据量查询分析
- 缓存失效机制

### v2.0.0

业务插件化：

```text
插件负责产品类型、属性、字段和筛选业务
主题负责模板、CSS 和 JavaScript
```

---

## 注意事项

为了方便集中学习，本项目仍将内容类型、分类法、字段、后台配置和查询逻辑写在主题的 `functions.php` 中。

正式商业项目更推荐将业务结构迁移到独立插件。

---

## License

GPL-2.0-or-later
