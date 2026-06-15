# WordPress 产品分类导航与 AJAX 多条件筛选主题

> 一套用于学习 WordPress 产品目录系统的经典主题，完整演示多层级分类导航、普通 GET 筛选、AJAX 无刷新更新、浏览器历史记录、主查询修改与渐进增强。

![Version](https://img.shields.io/badge/version-v1.2.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-Classic%20Theme-21759b)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb4)
![AJAX](https://img.shields.io/badge/AJAX-Progressive%20Enhancement-18794e)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

---

## 项目简介

本项目是一套面向 WordPress 经典主题开发的产品分类与筛选学习主题。

项目从产品目录的两个核心问题出发：

1. 如何根据当前分类路径逐级显示产品分类导航；
2. 如何在当前产品目录中通过多个条件组合筛选产品。

v1.2.0 在原有 GET 筛选基础上增加 AJAX 无刷新更新，同时保留普通页面请求作为降级方案。

```text
JavaScript 可用
→ AJAX 更新产品列表、数量和分页

JavaScript 不可用
→ 普通 GET 表单提交
```

这使主题既适合学习 WordPress 原生请求过程，也适合研究现代前端筛选交互。

---

## 当前版本

```text
v1.2.0
```

版本名称：

```text
AJAX 无刷新筛选版
```

---

## 核心功能

### 产品数据结构

- `product` 产品自定义文章类型
- `product_category` 多层级产品分类
- `product_brand` 产品品牌
- `product_voltage` 产品电压
- `product_feature` 功能特点
- `product_model` 产品型号
- `product_price` 产品价格
- `product_power` 产品功率

### 产品分类导航

- 始终显示一级分类
- 保留当前分类祖先路径
- 显示当前分类的直接下级
- 当前分类和祖先分类使用不同高亮
- 显示产品数量
- 面包屑导航
- 返回上一级分类
- 一次获取全部分类并建立父子索引
- 避免模板中的 N+1 分类查询

### 多条件筛选

- 品牌多选
- 电压多选
- 功能特点多选
- 价格范围单选
- 功率范围单选
- 产品排序
- 单项移除
- 清空单组
- 清空全部
- 参数白名单验证
- 无结果恢复入口

### AJAX 交互

- 选择条件后自动更新产品
- 排序变化后自动更新
- AJAX 更新产品数量
- AJAX 更新产品卡片
- AJAX 更新无结果状态
- AJAX 更新分页
- 取消尚未完成的旧请求
- 加载遮罩和旋转指示器
- 防抖请求
- 请求失败自动回退到普通页面
- 屏幕阅读器结果播报

### 地址栏与浏览器历史

- 筛选结果同步到地址栏
- URL 可以复制和分享
- 刷新页面可恢复筛选条件
- 浏览器后退恢复上一次筛选
- 浏览器前进恢复下一次筛选
- AJAX 分页同步页码参数
- `history.pushState()`
- `popstate`

### 后台管理

- 一键导入演示数据
- 产品图片列
- 产品型号列
- 产品分类列
- 产品品牌列
- 产品价格列
- 产品功率列
- 后台型号排序
- 后台价格排序
- 后台功率排序

---

## 技术架构

```text
产品分类导航
│
├── get_queried_object()
├── 产品分类父子索引
├── 当前分类路径
└── category-navigation.php

筛选表单
│
├── 普通 GET 表单
├── product-filter.js
├── AJAX 请求
└── History API

查询规则
│
├── pfl_get_product_query_parts()
├── pre_get_posts
├── tax_query
├── meta_query
└── 排序规则

结果渲染
│
├── results.php
├── card.php
├── no-results.php
└── 产品分页
```

---

## 渐进增强设计

v1.2.0 的重点不是简单地把页面改成 AJAX，而是保证两种请求方式共用相同规则。

### 普通页面请求

```text
筛选表单
↓
GET 参数
↓
pre_get_posts
↓
WordPress 主查询
↓
results.php
```

### AJAX 请求

```text
筛选表单
↓
JavaScript 收集条件
↓
admin-ajax.php
↓
pfl_ajax_filter_products()
↓
WP_Query
↓
results.php
↓
替换产品结果区域
```

两条路径共用：

```php
pfl_get_product_query_parts()
```

因此品牌、电压、功能、价格、功率和排序规则只维护一份。

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

## 关键文件职责

| 文件 | 职责 |
|---|---|
| `functions.php` | 数据注册、筛选规则、查询逻辑、AJAX 端点、后台管理 |
| `archive-product.php` | 全部产品归档结构 |
| `taxonomy-product_category.php` | 产品分类归档结构 |
| `filter-form.php` | 筛选条件表单和 AJAX 上下文 |
| `results.php` | 普通请求与 AJAX 共用的结果区域 |
| `card.php` | 单个产品卡片 |
| `no-results.php` | 无结果状态 |
| `category-navigation.php` | 多层级分类导航 |
| `product-filter.js` | AJAX、地址栏、历史记录、分页和筛选交互 |
| `product.css` | 导航、筛选、加载状态、产品列表和分页样式 |

---

## 安装方法

### 后台安装

```text
WordPress 后台
→ 外观
→ 主题
→ 安装主题
→ 上传主题
```

上传主题 ZIP 后启用。

### 手动安装

将主题目录复制到：

```text
wp-content/themes/
```

然后在后台启用。

---

## 从 v1.1.0 升级

主题目录名称保持不变：

```text
wp-product-navigation-filter-lab
```

可以使用新版本文件覆盖旧版本。

升级后建议：

1. 清除浏览器缓存；
2. 清除缓存插件缓存；
3. 进入“设置 → 固定链接”；
4. 点击一次“保存更改”。

演示产品和分类保存在数据库中，覆盖主题文件不会删除数据。

---

## 导入演示数据

进入：

```text
外观
→ 产品主题演示数据
```

点击：

```text
一键导入演示数据
```

系统会创建多层级分类、筛选属性和 12 条演示产品。

---

## 主要访问地址

### 全部产品

```text
/products/
```

### 产品分类

```text
/product-category/分类别名/
```

### 产品详情

```text
/products/产品别名/
```

---

## 分类数据示例

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

---

## 筛选参数示例

```text
/products/
?brand[]=huadong
&voltage[]=380v
&feature[]=automatic
&price_range=10000-30000
&power_range=3-5
&sort=price-asc
```

AJAX 更新后，地址栏仍会保留等价参数。

---

## 筛选逻辑

### 不同筛选组

```text
品牌
AND
电压
AND
功能
AND
价格
AND
功率
```

### 同一组

| 筛选组 | 逻辑 |
|---|---|
| 品牌 | `IN`，满足任意品牌 |
| 电压 | `IN`，满足任意电压 |
| 功能特点 | `AND`，同时具备全部功能 |
| 价格 | 单一区间 |
| 功率 | 单一区间 |

---

## 核心查询函数

### 查询规则

```php
pfl_get_product_query_parts()
```

根据筛选来源生成：

- taxonomy 查询片段
- meta 查询片段
- 排序参数

### 普通主查询

```php
pfl_filter_product_main_query()
```

通过：

```php
pre_get_posts
```

修改产品归档和分类归档的主查询。

### AJAX 查询

```php
pfl_ajax_filter_products()
```

处理：

- nonce 验证
- 筛选参数解析
- 分类上下文验证
- AJAX `WP_Query`
- 产品结果渲染
- 返回数量、分页和新 URL

---

## AJAX 请求流程

```text
用户选择筛选条件
↓
JavaScript 防抖
↓
取消旧请求
↓
发送 admin-ajax.php 请求
↓
服务器验证 nonce
↓
服务器执行 WP_Query
↓
服务器渲染 results.php
↓
替换 #product-results
↓
更新结果数量
↓
history.pushState 更新地址栏
```

---

## AJAX 分页

分页链接包含：

```html
data-product-page="2"
```

JavaScript 拦截链接后：

1. 保留当前筛选条件；
2. 请求指定页码；
3. 替换结果区域；
4. 更新地址栏中的 `paged`；
5. 平滑滚动到产品列表。

禁用 JavaScript 时，分页链接仍然是可以正常访问的普通 URL。

---

## 浏览器前进与后退

AJAX 筛选成功后调用：

```javascript
history.pushState()
```

当用户点击浏览器后退或前进时，监听：

```javascript
popstate
```

然后：

1. 从当前 URL 恢复表单状态；
2. 重新请求对应产品结果；
3. 不再次写入历史记录。

---

## 请求取消与防抖

筛选条件连续变化时使用：

```javascript
AbortController
```

取消旧请求，避免较慢的旧结果覆盖较新的筛选结果。

同时使用约 320ms 防抖，减少频繁请求。

---

## 请求失败降级

AJAX 请求失败时，主题会跳转到包含当前筛选参数的普通 URL。

```text
AJAX 失败
↓
普通 GET 页面请求
```

因此筛选功能不会完全依赖 JavaScript 或 AJAX。

---

## 无障碍处理

v1.2.0 增加：

- `aria-busy`
- `role="status"`
- `aria-live="polite"`
- 当前分页 `aria-current`
- 可键盘访问的真实表单控件
- 加载结果文字播报
- 减少动态效果系统设置支持

---

## v1.2.0 更新内容

- [x] AJAX 自动筛选
- [x] AJAX 排序
- [x] AJAX 分页
- [x] 产品数量无刷新更新
- [x] 产品卡片无刷新更新
- [x] 无结果状态无刷新更新
- [x] 加载遮罩和旋转动画
- [x] 请求防抖
- [x] AbortController 取消旧请求
- [x] History API 地址栏同步
- [x] 浏览器前进和后退恢复
- [x] 结果区域统一模板
- [x] GET 与 AJAX 共用查询规则
- [x] AJAX 失败自动降级
- [x] 移动端底部筛选操作栏
- [x] 屏幕阅读器状态播报

---

## 测试清单

### 普通筛选

- [ ] 单选品牌后产品自动变化
- [ ] 多选品牌后结果符合 OR 逻辑
- [ ] 多选功能后结果符合 AND 逻辑
- [ ] 价格区间筛选正确
- [ ] 功率区间筛选正确
- [ ] 排序变化后自动更新

### AJAX

- [ ] 页面没有整体刷新
- [ ] 结果数量正确更新
- [ ] 产品卡片正确更新
- [ ] 加载状态正常显示
- [ ] 快速连续点击不会显示旧结果

### 地址栏

- [ ] URL 同步筛选参数
- [ ] 复制 URL 后可恢复结果
- [ ] 刷新页面可恢复条件
- [ ] 浏览器后退可恢复上次结果
- [ ] 浏览器前进可恢复下一次结果

### 分页

- [ ] 筛选后的分页正常
- [ ] AJAX 分页不刷新整页
- [ ] 页码同步到地址栏
- [ ] 后退可以返回上一页结果

### 降级

- [ ] 禁用 JavaScript 后表单仍可提交
- [ ] 禁用 JavaScript 后分页仍可访问

---

## 常见问题

### 页面仍然使用旧 JavaScript

清除：

- 浏览器缓存
- WordPress 缓存插件
- 服务器缓存
- CDN 缓存

主题资源版本使用主题版本号，v1.2.0 正常情况下会生成新的资源 URL。

### AJAX 返回 403

通常与 nonce 缓存有关。清除页面缓存后重新加载。

### AJAX 返回 0

检查：

- `admin-ajax.php` 是否可以访问
- 主题是否完整覆盖
- `functions.php` 是否包含 AJAX action
- 浏览器控制台是否有脚本错误

### 筛选后地址栏没有变化

检查浏览器控制台，确认 `product-filter.js` 已正常加载。

### 后退后表单和结果不一致

确认没有缓存插件缓存带查询参数的 AJAX 响应。

---

## 后续规划

### v1.3.0：动态产品属性系统

计划增加：

- 产品材质
- 应用行业
- 产品用途
- 最低价和最高价输入
- 不同产品分类显示不同筛选组
- 分类专属属性配置
- 筛选项产品数量
- 禁用无结果选项

### v1.4.0：SEO 与产品内容增强

计划增加：

- 分类图片
- 分类顶部内容
- 分类底部 SEO 内容
- canonical
- 筛选参数索引控制
- 产品结构化数据
- 面包屑 Schema

### v2.0.0：业务插件化

计划把以下功能迁移到独立插件：

- 产品文章类型
- 产品分类法
- 产品属性
- 产品字段
- 查询逻辑
- AJAX 接口
- 演示数据工具

主题仅负责模板和视觉展示。

---

## 注意事项

为了方便学习，本项目仍将产品内容类型、分类法、查询规则和 AJAX 处理集中在主题的 `functions.php`。

正式项目中推荐：

```text
插件负责数据结构和业务逻辑
主题负责模板和样式
```

---

## License

GPL-2.0-or-later
