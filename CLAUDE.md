# CLAUDE.md — Plugin skd-seo

File này giúp agent hiểu ngay cấu trúc plugin mà không cần scan lại source. Đọc file này TRƯỚC khi sửa bất kỳ file nào trong plugin.

## Plugin này là gì

**skd-seo (Tùy Chỉnh Seo)** — lớp SEO của SkillDo CMS v8: thẻ head (title / description / keyword / OpenGraph / Twitter / hreflang / canonical), schema.org JSON-LD, `sitemap.xml`, `robots.txt`, `llms.txt`, chèn script bên thứ ba (analytics, chat…), chuyển hướng URL, nhật ký 404 và bộ **chấm điểm SEO** kiểu RankMath (metabox "Seo" trong form nội dung: focus keyword, robots, canonical, schema thủ công).

- **Namespace PHP: `SkdSeo\*`** → `app/`. `plugin.json` khai thêm PSR-4 tường minh `SkdSeo\Modules` → `app\Modules`.
- Main class: `SkdSeo` trong `skd-seo.php` — **không có namespace**, chứa luôn `active()/uninstall()` và `SkdSeo::header()` (điểm vào của toàn bộ meta head).
- Provider: `SkdSeo\Providers\SkdSeoServiceProvider`. Middleware nhóm `web`: `SkdSeo\Middlewares\RedirectIfMatched`.
- Hằng: `SKD_SEO_NAME`, `SKD_SEO_VERSION`, `SKD_SEO_PATH` (khai trong `skd-seo.php`).
- **Không có thư mục `language/`** — chuỗi hiển thị hardcode tiếng Việt. Giữ nguyên phong cách đó khi thêm code.
- Mọi file trong `bootstrap/` được **core tự động load** — thêm file mới vào đó là chạy, không cần include. Quy ước: mỗi file wire hook cho một mảng chức năng, và luôn tách nhánh `Admin::is()`.

## 4 luồng chính

### 1. Meta head + schema (frontend)

`skd-seo.php` → `Theme::config()->booted()` đăng ký `cle_header → SkdSeo::header` (priority 1) và `in_tag_html → SkdSeo::bodyTags`.

`SkdSeo::header()` chạy theo đúng thứ tự sau — muốn chèn gì vào head thì bám vào 2 filter in đậm:

1. `new HeadService()` — giá trị mặc định từ option `general_title|description|keyword`, `logo_header`.
2. Lấy title/description/keyword/image của trang: `Theme::isMethod('index')` → `Cms::getData('category')`; `Theme::isMethod('detail')` → `Cms::getData('object')`.
3. **`apply_filters('seo_head_base', $headService, $page)`** — nơi plugin/loại trang khác ghi đè nội dung cơ bản (`SeoTravel::headBase`, `SeoTag::headBase`).
4. Tự thêm OpenGraph, Twitter card, `fb:app_id`, geo, author, `google-site-verification`, `hreflang` (khi đa ngữ), `canonical = request()->url()`.
5. **`apply_filters('seo_render', $headService, $page)`** — nơi ghi đè robots / canonical / thêm meta. `AdminPoint::seoRender` chạy ở **priority 99** (thiết lập tay của biên tập viên phải thắng), `SeoTag::render` ở 20.
6. `$headService->render()` — echo `<title>`, các `<meta>`, các `addCode()`, rồi gọi `Schema::render()`.

`Schema::render()`: luôn có `WebSite` + `Organization`; `is_home()` → `LocalBusiness` (nếu bật); `products_detail` → `Product`; `post_index` → `tag()` nếu có data-bag `tag`, ngược lại `category()`; `post_detail` → `NewsArticle`; cuối cùng **`apply_filters('schema_render', $schemas, $page)`**.

> `HeadService::addMeta()` **dedupe theo `name`** — meta có tên (description, keywords, robots…) gọi nhiều lần chỉ ra một thẻ, lần sau ghi đè lần trước. Meta không tên (og:*, twitter: qua `addProperty`) thì nối thêm.

### 2. Sitemap / robots / llms

`routes/web.php` khai 3 route → `SeoController`. `SetLanguage::exclude()` trong provider loại 3 đường dẫn này khỏi middleware ngôn ngữ.

`SitemapService::sitemap()`:
- Không có `?p` → trang index, lấy danh sách từ **`apply_filters('seo_sitemap_list', [])`** (mỗi entry `['date' => DATE_ATOM]`).
- Có `?p={key}` → gọi **`apply_filters('seo_sitemap_{key}_xml', $sitemap, $type, $number, $request)`** (dấu `-` trong key đổi thành `_`).
- `?p={key}-{n}` → `{n}` tách ra thành tham số `$number` (phân trang, quy ước 200 mục/trang).
- `SitemapService::itemUrl()` **tự thêm tiền tố ngôn ngữ** cho site đa ngữ → truyền slug thô vào, đừng đi qua `Url::permalink()`/`Url::tag()` nữa kẻo prefix hai lần.

`llms.txt` / `llms-full.txt`: `LlmsService::build()` dựng `LlmsContent` (trang, danh mục, bài viết, sản phẩm, thẻ) rồi **`apply_filters('skd_seo_llms_content', $llms)`**; `LlmsService::full()` dựng bản toàn văn (bóc thẻ HTML) qua **`skd_seo_llms_full`**. Group rỗng không render. Số mục mỗi nhóm lấy từ option `seo_llms[limit]`, bản toàn văn còn bị chặn thêm ở `LIMIT_FULL`.

`robots.txt`: `RobotsService::content()` = nội dung admin tự viết (option `skd_seo_robots`, rỗng thì dựng mặc định) + khối chặn crawler AI **chỉ khi** admin chọn "Không cho phép" + dòng chú thích trỏ tới `llms.txt`. Lưu ý: robots.txt **không có chỉ thị chuẩn** cho llms.txt nên đó là comment `#`, không phải directive.

### 3. Chấm điểm SEO & thiết lập thủ công ("Point")

Bật/tắt bằng option `seo_point`; danh sách module áp dụng ở option `seo_point_support` (Cấu hình > Seo > Chấm điểm seo, **chỉ root thấy**).

- `bootstrap/point.php`: admin → `add_meta_box` (hiện metabox) + `save_object` (lưu); frontend → `schema_render` + `seo_render` ở priority 99.
- `SeoPoint::module()` là **registry module → class**, mở rộng qua filter `seo_point_admin_module_enable`. Mỗi class cài 8 method `get/set{FocusKeyword,Robots,Canonical,Schema}` + `schemaRender($page,$object)` + `seoRender($page,$object)`, dữ liệu lưu bằng `Model::updateMeta()`.
- `AdminPoint::schemaRender/seoRender` **duyệt tuần tự các module và dừng ở module đầu tiên trả về dữ liệu** → 2 module cùng ăn một `$page` sẽ tranh nhau. Vì thế module nào dùng chung tên trang phải tự kiểm tra kiểu đối tượng (xem `Category` vs `Tag`, cả hai đều là `post_index`).
- Đối tượng để đọc metadata: `AdminPoint::object()` lấy `Cms::getData('object')` (trang chi tiết), **rỗng thì lấy `Cms::getData('category')`** (trang lưu trữ: danh mục bài viết, danh mục sản phẩm, thẻ). Data-bag khác hai cái đó (travel dùng `tour`, `archive`) phải ánh xạ lại qua filter **`seo_point_object`**.
- Bộ tiêu chí chấm điểm: `SeoPoint::criteria($module)` + filter **`seo_point_criteria`** — form thiếu trường (vd form thẻ không có editor nội dung) thì rút gọn danh sách, JS tự chia điểm theo số tiêu chí thực tế.

### 4. Chuyển hướng & nhật ký 404

- `RedirectIfMatched` (middleware nhóm web) tra bảng `redirect` theo `path`, cache `seo_redirect_{md5(path)}` 30 phút, giữ query string, `header('Location')` + `exit`.
- `Log404::handle()` treo trên action `template_redirect`: response 404 thì ghi/tăng `hit` bảng `log404`, và chuyển hướng nếu dòng đó bật hoặc theo cấu hình chung.
- Cả hai module đều là "trang cấu hình" (`admin_system_tabs`, nhóm `marketing`), **không phải module CRUD có menu riêng**. Sửa từng dòng bằng modal + ajax riêng, không qua form save chuẩn.

## Map file

### Gốc plugin

| File | Chức năng |
|---|---|
| `plugin.json` | Manifest: version, provider, PSR-4 `SkdSeo\Modules`, đăng ký middleware `RedirectIfMatched` vào nhóm `web` |
| `skd-seo.php` | Hằng + class `SkdSeo`: `active()/uninstall()`, `bodyTags()`, `buildAlternateLinks()` (hreflang), **`header()`** (toàn bộ meta head), và block đăng ký hook cuối file (admin: menu Marketing; frontend: breadcrumb schema + `cle_header` + các sitemap page/post/post-category/product/product-category) |
| `routes/web.php` | `/sitemap.xml`, `/robots.txt`, `/llms.txt`, `/llms-full.txt` → `SeoController`. Cả 4 phải có trong `SetLanguage::exclude()` của provider |
| `database/database.php` | Migration cài đặt: tạo bảng `redirect` + `log404`. **Chỉ file này được chạy** (gọi từ `SkdSeo::active()/uninstall()`) |
| `database/db_v3.1.0.php`, `db_v3.3.2.php`, `db_v4.0.0.php` | Migration theo phiên bản của cơ chế cũ — **không còn được chạy**, giữ để tham khảo |
| `config/log404.php` | Default `enabled / redirect / link`, merge với option `seo_404` trong provider (`skd-seo::log404.*`) |
| `config/point.php` | **File rỗng** |

### bootstrap/ (auto-load)

| File | Wire cái gì |
|---|---|
| `web.php` | `cle_header`/`cle_footer` → `ScriptService` (echo option `header_script` / `footer_script`) |
| `system.php` | Tab "Seo" trong Cấu hình hệ thống + thứ tự các khối (`admin_system_seo_html`: general 10, product 15, script 20, schema 30, robots 40, point 40 chỉ root, redirect 50) + hook lưu `admin_system_seo_save` |
| `point.php` | Chấm điểm seo (xem mục 3). Toàn bộ block bọc trong `if(Option::get('seo_point'))` |
| `redirect.php` | Tab "Chuyển Hướng" + form/table/hook lưu-xóa module `seo_redirect` |
| `log404.php` | Tab "Log 404" + `template_redirect` → `Log404::handle` |
| `ajax.php` | Registry ajax: `SkdSeo\Ajax\Redirect::save`, `SkdSeo\Ajax\Long404::save` (⚠ xem Gotcha) |
| `travel.php` | Cầu nối plugin travel — mọi callback tự kiểm tra `class_exists` trước |
| `tag.php` | Cầu nối chức năng Thẻ của CMS 8.1.3+ — mọi callback tự kiểm tra `SeoTag::support()` |

### app/Services/ — phần lõi

| File | Chức năng |
|---|---|
| `HeadService.php` | Đối tượng thu thập head: `setTitle/Description/Keyword/Image`, `addMeta` (dedupe theo name), `addProperty` (og:*), `addItemprop`, `addCode` (thẻ `<link>` thô), `render()`. Giữ sẵn một `Schema` |
| `Schema.php` | Sinh JSON-LD: `website()` (WebSite + Organization, có `sameAs`/`@id`), `home()` (LocalBusiness), `product()` (gộp rating từ plugin rating-star), `post()` (NewsArticle + `keywords` từ thẻ), `tag()` (CollectionPage + ItemList), `category()`, `breadcrumb()` (BreadcrumbList), `render()`. Helper tĩnh dùng chung: `sameAs()`, `country()` |
| `RobotsService.php` | Nội dung `robots.txt` + danh sách 16 crawler AI (`aiAgents()`), cờ cho phép/chặn (`allowAi()`) |
| `Llms/LlmsService.php` | Dựng `llms.txt` (`build()`) và `llms-full.txt` (`full()`), đọc cấu hình option `seo_llms` |
| `ScriptService.php` | Echo `header_script` / `footer_script`. (`body_script` do **theme** render, không phải plugin) |
| `SitemapService.php` | Bộ dựng XML: `item()` (sitemap index), `itemHome()`, `itemUrl()` — tất cả tự nhân bản theo ngôn ngữ khi đa ngữ |
| `Sitemap{Page,Post,PostCategory,Product,ProductCategory}.php` | Từng nguồn dữ liệu. `Post`/`Product` có phân trang 200/trang; `Product*` chỉ đăng ký khi có alias `Product`/`ProductCategory` |
| `SitemapTag.php` | Thẻ — chỉ thẻ `count > 0`, url ghép từ `cms.tag.prefix`, bật/tắt bằng option `seo_tag[sitemap]` |
| `Sitemap{Tour,TourCategory,TourArchive}.php` | Plugin travel, đều có `support()` kiểm tra `class_exists` |
| `SeoTravel.php` | Cầu nối travel: ánh xạ data-bag `tour|category|archive` sang head, point object/module/support, llms |
| `SeoTag.php` | Cầu nối chức năng Thẻ: mô tả mặc định cho thẻ trống, title trang 2+, **noindex thẻ mỏng**, `article:tag` + keywords cho bài viết, point object/module/support/criteria, llms. Có `postTagNames()` nhớ kết quả trong request (dùng chung với `Schema::post`) |
| `SchemaTravel.php` | Schema riêng của travel (TouristTrip, FAQPage, ItemList) |
| `Llms/LlmsContent.php`, `Llms/LlmsGroup.php` | Cây nội dung `llms.txt`; group khai sẵn (page/post/category/tag/product/…), `addGroup()` để thêm |
| `Admin/SeoMarketing.php` | Menu "Marketing" trong admin + nhóm `marketing` cho cấu hình hệ thống; JS ẩn menu khi không có submenu |

### app/Supports/

- `SeoPoint.php` — registry module chấm điểm (`module()`), danh sách 17 tiêu chí (`listCriteria()`), bộ tiêu chí theo module (`criteria()`), đăng ký metabox (`registerMetabox()` — có xử lý riêng cho key dạng `post_{postType}` / `post_categories_{cateType}`).
- `SKDSeoSchemaBreadcrumb.php` — bơm microdata `BreadcrumbList` vào breadcrumb của theme qua 4 filter `breadcrumb_*`.

### app/Modules/

| Thư mục | Nội dung |
|---|---|
| `Point/AdminPoint.php` | Render metabox "Seo" (3 tab: chung / nâng cao / schema), `save()` (đọc request → gọi setter của module), `schemaRender()`/`seoRender()` (frontend, duyệt module) |
| `Point/Modules/` | Mỗi lớp gắn với một tên trang + một kiểu đối tượng: `Post`(post_detail), `Category`(post_index + `PostCategory`), `Tag`(post_index + `Tag`), `Page`(page_detail), `Product`(products_detail), `ProductCategory`(products_index), `Tour`(tour_detail), `TourCategory`(tour_index) |
| `System/AdminSystem.php` | Toàn bộ khối cấu hình Seo + `save()` chung |
| `System/AdminSystemTravel.php` | Khối meta cho trang danh sách tour |
| `System/AdminSystemTag.php` | Khối "Trang Thẻ": bật/tắt sitemap thẻ + số bài tối thiểu để index |
| `System/AdminSystemLlms.php` | Khối "Công cụ tìm kiếm AI": cho phép/chặn crawler AI, bật `llms-full.txt`, số mục mỗi nhóm |
| `Redirect/` | `AdminRedirect` (tab + trang list/add), `Form` (FormAdmin cho module `seo_redirect`), `Table` (SKDObjectTable) |
| `Log404/` | `AdminLog404` (tab + trang list), `Log404` (bắt 404 runtime), `Form`, `Table` |

### app/ còn lại

- `Controllers/Web/SeoController.php` — 4 endpoint mỏng: `sitemap()`, `robots()`, `llms()`, `llmsFull()`. Toàn bộ phần dựng nội dung nằm ở `SitemapService` / `RobotsService` / `LlmsService`.
- `Middlewares/RedirectIfMatched.php` — chuyển hướng theo bảng `redirect`.
- `Models/Redirect.php` (bảng `redirect`), `Models/Log404.php` (bảng `log404`) — model thuần, không route/language.
- `Ajax/Redirect.php`, `Ajax/Log404.php` — lưu nhanh 1 dòng từ modal, tự xóa cache `seo_redirect_*`.
- `views/point/point.blade.php` — metabox Seo (nhận `$formRobots`, `$formCanonical`, `$formSchema`, `$focusKeyword`, `$criteria`) + JS chấm điểm. `views/redirect/script.blade.php`, `views/404/script.blade.php` — modal sửa nhanh.
- `assets/main-sitemap.xsl` — stylesheet hiển thị sitemap. `assets/thumb.png` — ảnh plugin.

## Option & config

| Key | Nội dung |
|---|---|
| `general_title|description|keyword`, `general_label`, `logo_header`, `site_social_image`, `seo_favicon` | Meta mặc định toàn site |
| `product_title|description|keyword` | Meta trang danh sách sản phẩm |
| `tour_title|description|keyword` | Meta trang danh sách tour (travel) |
| `header_script`, `body_script`, `footer_script` | Script bên thứ ba. Provider quét URL trong 3 option này để **nới CSP** (`security-headers.content_security_policy`) và loại chúng khỏi `request-sanitizer` khi ở admin |
| `skd_seo_robots` | Nội dung `robots.txt` |
| `schemaLocalBusiness` | `[enabled, addressLocality, openingHours]` |
| `seo_point`, `seo_point_support` | Bật chấm điểm seo + danh sách module áp dụng |
| `seo_404` | `[enabled, redirect, link]` → merge vào config `skd-seo::log404` |
| `seo_tag` | `[sitemap, min_count]` — cấu hình seo trang thẻ |
| `seo_llms` | `[ai_bots, full, limit]` — crawler AI, `llms-full.txt`, số mục mỗi nhóm |
| `seo_social_profiles` | Hồ sơ mạng xã hội, mỗi dòng 1 URL → `sameAs` của Organization / LocalBusiness |
| `seo_country` | Mã quốc gia ISO cho `addressCountry` (mặc định `VN`) |
| `facebook_app_id`, `facebook_admins`, `seo_google_masterkey` | Verification / OG |

## Database

Plugin tự tạo 2 bảng (prefix `DB_PREFIX`): `redirect` (path → to, type 301/302, cờ `redirect`), `log404` (path, ip, hit, to, cờ `redirect`).

Metadata SEO **không có bảng riêng**: đi qua `Model::updateMeta()` → `Metadata` tự chọn bảng `{table}_metadata` nếu có, không thì đổ vào bảng chung `metabox` với `object_type = {table}`. Vì vậy bật point cho một model mới **không cần migration**.

## Hook mở rộng (bảng tra nhanh)

| Hook | Loại | Tham số | Dùng để |
|---|---|---|---|
| `seo_head_base` | filter | `$headService, $page` | Ghi đè title/description/keyword/image theo loại trang |
| `seo_render` | filter | `$headService, $page` | Thêm/ghi đè meta, robots, canonical (AdminPoint@99) |
| `schema_render` | filter | `$schemas, $page` | Thêm/thay JSON-LD |
| `seo_title|description|keyword|image|auth` | filter | giá trị | Can thiệp từng trường lẻ |
| `seo_sitemap_list` | filter | `$list` | Đăng ký một sitemap con |
| `seo_sitemap_{key}_xml` | filter | `$sitemap, $type, $number, $request` | Sinh XML cho sitemap con |
| `skd_seo_llms_content` | filter | `$llms` | Bổ sung nhóm/mục vào `llms.txt` |
| `skd_seo_llms_full` | filter | `$text` | Can thiệp nội dung `llms-full.txt` |
| `skd_seo_robots_content` | filter | `$text` | Can thiệp nội dung `robots.txt` |
| `skd_seo_ai_agents` | filter | `$agents` | Thêm/bớt crawler AI trong danh sách chặn |
| `schema_same_as`, `schema_country`, `schema_author_name` | filter | giá trị | Ghi đè `sameAs` / mã quốc gia / tác giả bài viết |
| `seo_point_support_module` | filter | `$modules` | Thêm module vào danh sách chọn ở Cấu hình |
| `seo_point_admin_module_enable` | filter | `$modules` | Khai class đọc/ghi metadata seo của module |
| `seo_point_object` | filter | `$object, $page` | Ánh xạ đối tượng đang hiển thị cho trang không dùng data-bag `object` |
| `seo_point_criteria` | filter | `$criteria, $module` | Rút gọn bộ tiêu chí chấm điểm của một module |
| `seo_schema`, `seo_robots`, `seo_canonical` | filter | `$value, $object` | Can thiệp giá trị thủ công trước khi xuất |
| `seo_tag_description_default`, `seo_tag_robots_thin` | filter | `$value, $tag` | Tùy biến mô tả mặc định / robots của thẻ mỏng |

## Quy tắc khi sửa plugin này

1. **Code/comment tiếng Việt**, không có i18n — giữ nguyên phong cách.
2. **Hỗ trợ một loại nội dung mới = 1 file `Services/Seo{X}.php` + 1 file `bootstrap/{x}.php`**, theo đúng mẫu `SeoTravel` / `SeoTag`: hook đăng ký vô điều kiện, `support()` kiểm tra `class_exists` **bên trong** callback (bootstrap các plugin chạy theo thứ tự nạp, class có thể chưa tồn tại lúc đăng ký).
3. **Thêm module chấm điểm** = class 8 getter/setter + `schemaRender`/`seoRender`, khai qua `seo_point_admin_module_enable` **và** `seo_point_support_module`, rồi ánh xạ đối tượng qua `seo_point_object` nếu trang không dùng `Cms::getData('object')`.
4. **Trang dùng chung `$page` phải kiểm tra kiểu đối tượng** trong `seoRender`/`schemaRender` — vòng lặp module dừng ở kết quả đầu tiên, module "trùng trang" sẽ nuốt mất module sau và đọc nhầm bảng metadata.
5. **Sitemap**: dùng slug thô trong `itemUrl()`; muốn phân trang thì theo mẫu `SitemapPost` (`?p={key}-{n}`, 200 mục/trang).
6. Nhớ 2 lớp cache: `seo_redirect_{md5(path)}` (chuyển hướng) và cache của core (`tag_*`, `product_detail_*`…) — sửa dữ liệu nguồn thì xóa đúng key.
7. `HeadService::addMeta` dedupe theo `name` — muốn nhiều thẻ cùng loại (og:*, article:tag) phải dùng `addProperty`.
8. Đổi giao diện metabox point: `views/point/point.blade.php` nhận `$criteria` từ `AdminPoint::metaBox` và JS chia điểm theo `criteriaKeys.length` — thêm tiêu chí mới phải khai cả ở `SeoPoint::listCriteria()` lẫn nhánh cộng điểm `seoPointAdd('key')`.

## Gotcha còn tồn tại (đã verify)

- `Log404::handle()` `select('redirect','to','hit')` không lấy `id` rồi dùng `$log404->id` để update → nhánh tăng `hit` cho 404 đã có bản ghi không chạy đúng.
- `Log404::handle()` đọc cấu hình bằng key `plugin.skd-seo.log404.*` (sai định dạng — đúng là `skd-seo::log404.*`) nên luôn nhận giá trị mặc định.
- `RedirectIfMatched` **bỏ qua cột `redirect` (bật/tắt từng dòng)** và chỉ chạy khi `skd-seo::log404.redirect` khác rỗng → module Chuyển Hướng đang phụ thuộc vào cấu hình của mục 404.
- `Modules/Log404/Form.php`: rule unique trỏ bảng `redirect` (đáng lẽ `log404`) và dùng key `handlerValue` trong khi `Unique` đọc `handleValue` → closure chuẩn hóa path bị bỏ qua. Ít ảnh hưởng vì `AdminLog404::render()` không có nhánh `add` — form này gần như không tới được.
- `SitemapProductCategory::sitemap()` khai thêm `itemUrl('/')` trong khi `SitemapPage` đã có `itemHome()` → trang chủ xuất hiện 2 lần trong sitemap.
- `db_v4.0.0.php` tạo bản ghi `Router` trỏ `App\Controllers\Web\SeoController` (sai namespace, đúng là `SkdSeo\Controllers\Web`) — vô hại vì file này không còn được chạy và `routes/web.php` đã khai 3 route.
- `SKD_SEO_VERSION` trong `skd-seo.php` (4.0.8) **lệch** `plugin.json` (5.3.0) và không được đọc ở đâu — nguồn phiên bản thật là `plugin.json`. (`SKD_SEO_PATH` thì có dùng, trong `SitemapService`.)
- `SeoPoint::registerMetabox()` `foreach` thẳng `Option::get('seo_point_support')` — option chưa từng lưu (null) sẽ sinh warning.
- `AdminSystem::renderRedirect()` đặt tiêu đề khối là "Chấm điểm seo" (copy nhầm từ `renderPoint`), nội dung thực tế là cấu hình 404.
- `assets/style.css` và `assets/images/skd-seo.png` không được nạp ở đâu.
- Plugin khác type-hint cứng `\SkdSeo\Services\HeadService` trong filter `seo_render` (vd `Ecommerce\Controllers\Web\EcommerceController`) → tắt skd-seo sẽ lỗi. Khi đổi chữ ký `HeadService` phải rà các plugin đó.

## Bug đã sửa

- **`llms.txt` không có một link hợp lệ nào**: `LlmsGroup::render()` nối `'] ('` — có khoảng trắng giữa `]` và `(` nên không dòng nào là link Markdown. Đã bỏ khoảng trắng.
- **`publisher.name` của NewsArticle là tiêu đề bài viết**: `Schema::post()`/`category()` dùng `$this->title` (đã bị `setTitle` ghi đè) → mỗi bài khai một nhà xuất bản khác nhau. Đã đổi sang `publisherName()` = `general_label`.
- **`dateModified` luôn bằng thời điểm mở trang**: `date(DATE_ATOM)` không truyền timestamp. Đã đổi sang `$item->updated` (rỗng thì `created`).
- **`addressLocality` nhận tên tỉnh/thành**: theo schema.org tỉnh/thành cấp 1 là `addressRegion`, `addressLocality` là quận/huyện/phường. Đã đổi cấp (giữ nguyên tên option `schemaLocalBusiness[addressLocality]` để không mất dữ liệu đã lưu).
- **Ajax trang Log 404**: `bootstrap/ajax.php` + `views/404/script.blade.php` gọi `SkdSeo\Ajax\Long404::save` trong khi class thật là `Log404` → modal sửa chuyển hướng không lưu được. Đã đổi đúng tên ở cả 2 nơi.
- **Chấm điểm seo cho sản phẩm chưa từng chạy**: `Modules/Point/Modules/product.php` + `product_category.php` là code v7 (class global `SKD_Seo_Product_Point` / `SKD_Seo_Product_Category_Point`, không namespace nên PSR-4 không nạp) và cũng không được khai trong `SeoPoint::module()`, dù Cấu hình > Seo vẫn hiện 2 ô tick. Đã thay bằng `Product.php` / `ProductCategory.php` chuẩn namespace và đăng ký trong `SeoPoint::module()` kèm `class_exists(\Ecommerce\Models\Product::class)`.
- **Thiết lập seo thủ công của trang lưu trữ không xuất ra ngoài trang**: `AdminPoint::object()` chỉ đọc `Cms::getData('object')` — trang danh mục bài viết / danh mục sản phẩm / thẻ đặt đối tượng ở `category` nên metabox lưu được nhưng frontend không bao giờ đọc tới. Đã thêm nhánh dự phòng `category`. ⚠ Đây là **đổi hành vi**: site nào từng đặt No Index / Canonical cho danh mục thì từ nay thiết lập đó bắt đầu có hiệu lực thật.

## Lịch sử thay đổi đáng nhớ

- **2026-08 — GEO (tối ưu cho công cụ tìm kiếm AI)**: tách `LlmsService` khỏi `SeoController`, đổ bài viết + sản phẩm vào `llms.txt` (nhóm `post` trước đó luôn rỗng), thêm `/llms-full.txt`; `RobotsService` với khối chặn 16 crawler AI + chú thích trỏ `llms.txt`; `Organization`/`LocalBusiness` có `sameAs` + `@id` + `address`; thêm `BreadcrumbList` JSON-LD; OG `article:published_time`/`article:modified_time` và `og:type=article` cho trang bài viết; khối cấu hình "Công cụ tìm kiếm AI".
  - `Schema::breadcrumb()` **không dùng `ThemeBreadcrumb::instance()`** mà tự gọi lại đúng 2 filter `theme_breadcrumb_*_data`: head render trước body, đụng vào singleton sẽ chốt dữ liệu cho breadcrumb hiển thị bên dưới.
  - Theme vẫn xuất BreadcrumbList dạng microdata (`SKDSeoSchemaBreadcrumb`) — hai bản mô tả cùng một danh sách, công cụ tìm kiếm gộp lại; bản JSON-LD thêm vào để trình đọc chỉ hiểu JSON-LD vẫn thấy breadcrumb.


- **2026-08 — hỗ trợ chức năng Thẻ (CMS 8.1.3)**: thêm `Services/SeoTag.php`, `Services/SitemapTag.php`, `Modules/Point/Modules/Tag.php`, `Modules/System/AdminSystemTag.php`, `bootstrap/tag.php`; `Schema` thêm `tag()` (CollectionPage thay vì NewsArticle cho trang lưu trữ) và `keywords` cho bài viết; `Point\Modules\Category` thêm kiểm tra `instanceof PostCategory` (trang thẻ cũng báo `post_index`, không chặn thì đọc nhầm `categories_metadata`); bộ tiêu chí chấm điểm tách theo module + vá lỗi `tinymce.get(...)` ném exception trên form không có editor.
