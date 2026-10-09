<!-- TAB NAVIGATION -->
<ul class="nav nav-tabs nav-tabs-horizontal mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" href="#seo-general" role="tab" data-bs-toggle="tab">
            <i class="fal fa-cog"></i> Cấu hình chung
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="#seo-advanced" role="tab" data-bs-toggle="tab"><i class="fal fa-box-full"></i> Nâng cao</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="#seo-schema" role="tab" data-bs-toggle="tab"><i class="fal fa-box-full"></i> Schema</a>
    </li>
</ul>
<!-- TAB CONTENT -->
<div class="tab-content" style="padding:10px;">
    <div class="tab-pane fade show active" id="seo-general">
        <div class="form-group">
            <label for="seo_focus_keyword">Từ khóa chính</label>
            <div class="input-group">
                <input name="seo_focus_keyword" type="text" class="form-control" id="seo_focus_keyword" value="{{$focusKeyword}}">
                <span class="input-group-addon seo-point-score"><span id="seo_point">0</span>/100</span>
            </div>
            <p style="margin: 5px 0; color: #999;">Cụm từ bạn muốn trang này xếp hạng trên Google.</p>
        </div>

        <div class="seo-serp-preview">
            <div class="seo-serp-label">Xem trước trên Google</div>
            <div class="seo-serp-url js_seo_serp_url"></div>
            <div class="seo-serp-title js_seo_serp_title"></div>
            <div class="seo-serp-desc js_seo_serp_desc"></div>
        </div>

        <div class="panel-group seo-panel-group" id="seo-group-panel" role="tablist" aria-multiselectable="true">
            <div class="panel panel-default">
                <div id="seo_panel_base" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="seo_panel_heading_base">
                    <div class="panel-body">
                        <ul>
                            @php($criteria = (isset($criteria) && hasItems($criteria)) ? $criteria : \SkdSeo\Supports\SeoPoint::listCriteria())
                            @php($pointWeights = $pointConfig['weights'] ?? \SkdSeo\Supports\SeoPoint::weights())
                            @php($criteria = \SkdSeo\Supports\SeoPoint::sortByImportance($criteria, $pointWeights))
                            @foreach ($criteria as $key => $label)
                                @php($level = \SkdSeo\Supports\SeoPoint::importance($pointWeights[$key] ?? 1))
                                <li key="{{$key}}" class="seo-check-{{$key}} test-fail">
                                    <span class="icon"><i class="fal fa-times"></i></span>
                                    <span class="txt">{{$label}}</span>
                                    <span class="seo-importance seo-importance-{{$level['key']}}" title="{{$level['hint']}}">{{$level['label']}}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="seo-advanced">
        <h4 class="box-title">Robots Meta</h4>
        <div class="row">
            {!! $formRobots->html() !!}
        </div>
        <div class="row">
            {!! $formCanonical->html() !!}
        </div>
    </div>
    <div class="tab-pane fade" id="seo-schema">
        {!! $formSchema->html() !!}
    </div>
</div>

<style>
    .seo-panel-group ul li {
        font-size: 14px;
        line-height: 25px;
        position: relative;
        clear: both;
        color: #5a6065;
        margin-bottom: 8px;
        display: flex;
        align-items: flex-start;
    }
    .seo-panel-group li span.icon {
        color:#fff;
        flex: 0 0 25px;
        width: 25px; height: 25px; line-height: 25px;
        text-align: center;
        display: inline-block;border-radius: 50%;
        margin-right: 8px;
    }
    .seo-panel-group li.test-fail span.icon {
        background-color: #F29C96;
    }
    .seo-panel-group li.test-success span.icon {
        background-color: var(--green);
    }
    .seo-panel-group li span.txt { flex: 1 1 auto; }
    .seo-importance {
        flex: 0 0 auto;
        margin-left: 8px; padding: 0 8px;
        font-size: 11px; line-height: 20px; font-weight: 600;
        border-radius: 10px; white-space: nowrap;
        margin-top: 2px; cursor: help;
    }
    .seo-importance-high { background-color: #fde2e0; color: #c0392b; }
    .seo-importance-medium { background-color: #fff1d6; color: #b9770e; }
    .seo-importance-low { background-color: #eef0f2; color: #6c757d; }
    .seo-point-score.is-bad { background-color: #fde2e0; color: #c0392b; }
    .seo-point-score.is-ok { background-color: #fff1d6; color: #b9770e; }
    .seo-point-score.is-good { background-color: #dff5e3; color: #1e8449; }
    .seo-serp-preview {
        border: 1px solid #e5e5e5; border-radius: 8px;
        padding: 12px 14px; margin-bottom: 15px;
        font-family: Arial, sans-serif; background: #fff;
    }
    .seo-serp-label { font-size: 12px; color: #999; margin-bottom: 6px; }
    .seo-serp-url { font-size: 13px; color: #202124; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .seo-serp-title { font-size: 20px; line-height: 26px; color: #1a0dab; margin: 2px 0; }
    .seo-serp-desc { font-size: 14px; line-height: 22px; color: #4d5156; }
</style>

<script>
    $(function () {

        /*
        |--------------------------------------------------------------------------
        | Chấm điểm SEO (bộ tiêu chí 2026, skd-seo 6.1.0)
        |--------------------------------------------------------------------------
        | Nguyên tắc: chấm trên đúng thứ khách và Google THẤY ngoài trang, không
        | phải từng ô nhập riêng lẻ.
        |  - Tiêu đề = Meta title (seo_title) nếu có, trống thì là ô Tiêu đề, cộng
        |    hậu tố tên thương hiệu như HeadService::documentTitle().
        |  - Mô tả = Meta description nếu có, trống thì là mô tả ngắn (excerpt),
        |    giống SkdSeo::header().
        |  - Ô Tiêu đề được giao diện in ra làm H1, nên nội dung KHÔNG được có H1
        |    và cũng không đòi H1 trong nội dung.
        | Form không có trình soạn thảo nội dung / ô ảnh đại diện thì các tiêu chí
        | tương ứng tự bị bỏ khỏi danh sách và khỏi mẫu số.
        */
        let config = <?php echo json_encode($pointConfig ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        let weights = config.weights || {};

        let settings = $.extend({minWords: 300, longWords: 300}, config.settings || {});

        let language = '<?php echo Language::default();?>';

        let seoPanel = $('#seo-general');

        let icon = {
            'error'   : '<i class="fal fa-times"></i>',
            'success' : '<i class="fal fa-check"></i>'
        };

        //Câu báo khi ĐẠT. Khi chưa đạt dùng câu gợi ý trong SeoPoint::listCriteria()
        let messageSuccess = {
            'keywordNotUsed'           : 'Đã đặt từ khóa chính.',
            'keywordUnique'            : 'Từ khóa chính chưa được dùng cho nội dung nào khác.',
            'keywordInTitle'           : 'Tiêu đề có chứa từ khóa chính.',
            'titleStartWithKeyword'    : 'Từ khóa chính nằm ở nửa đầu tiêu đề.',
            'keywordInMetaDescription' : 'Mô tả có chứa từ khóa chính.',
            'keywordInPermalink'       : 'Đường dẫn có chứa từ khóa chính.',
            'keywordIn10Percent'       : 'Từ khóa chính xuất hiện ngay ở đoạn mở đầu.',
            'keywordInContent'         : 'Nội dung có sử dụng từ khóa chính.',
            'keywordInSubheadings'     : 'Từ khóa chính có trong tiêu đề phụ.',
            'noH1InContent'            : 'Nội dung không có thẻ H1 (giao diện đã dùng tiêu đề làm H1).',
            'contentHasLists'          : 'Nội dung có danh sách hoặc bảng.',
            'linksHasInternal'         : 'Nội dung có liên kết nội bộ.',
            'linksHasExternal'         : 'Nội dung có dẫn nguồn ra website bên ngoài.',
            'hasFeaturedImage'         : 'Đã có ảnh đại diện.'
        };

        let criteriaLabels = {};

        seoPanel.find('li[key]').each(function () {
            criteriaLabels[$(this).attr('key')] = $(this).find('span.txt').text();
        });

        let criteriaKeys = Object.keys(criteriaLabels);

        let contentCriteria = [
            'keywordIn10Percent', 'keywordInContent', 'keywordInSubheadings', 'keywordStuffing',
            'contentNotThin', 'noH1InContent', 'contentHasSubheadings', 'contentHasShortParagraphs',
            'contentHasLists', 'linksHasInternal', 'linksHasExternal', 'imagesHaveAlt'
        ];

        /*
        | Các ô của form. Ô Tiêu đề / Nội dung / Mô tả ngắn đa ngôn ngữ có id
        | {lang}_*; danh mục / thẻ dùng {lang}_name thay cho {lang}_title. Ô slug
        | là #slug, hoặc {lang}_slug khi bật slug theo ngôn ngữ (8.2.0).
        */
        function field(ids) {
            for (let i = 0; i < ids.length; i++) {
                let el = $(ids[i]);
                if (el.length) return el.first();
            }
            return $();
        }

        let fields = {
            keyword     : $('#seo_focus_keyword'),
            title       : field(['#'+language+'_title', '#'+language+'_name']),
            seoTitle    : field(['#seo_title', 'input[name="seo_title"]']),
            description : field(['#seo_description', '[name="seo_description"]']),
            excerpt     : field(['#'+language+'_excerpt']),
            content     : field(['#'+language+'_content']),
            slug        : field(['#slug', '#'+language+'_slug', 'input[name="'+language+'[slug]"]']),
            image       : field(['#image', 'input[name="image"]'])
        };

        let hasContent = fields.content.length > 0;

        let hasImage = fields.image.length > 0;

        criteriaKeys = criteriaKeys.filter(function (key) {
            let keep = true;
            if (!hasContent && contentCriteria.indexOf(key) !== -1) keep = false;
            if (!hasImage && key === 'hasFeaturedImage') keep = false;
            if (!keep) seoPanel.find('li[key="'+key+'"]').hide();
            return keep;
        });

        let domainHost = '';

        try { domainHost = new URL(domain).host.toLowerCase(); } catch (e) {}

        /*
        | Trạng thái kiểm tra trùng từ khóa (ajax). null = chưa có kết quả, coi
        | như đạt để điểm không nhảy lên xuống trong lúc chờ.
        */
        let unique = {keyword: null, items: null};

        //------------------------------------------------------------------ helpers

        function norm(value) {
            return (value || '').toString().normalize('NFC').toLowerCase().replace(/\s+/g, ' ').trim();
        }

        function has(text, keyword) {
            return keyword.length > 0 && text.indexOf(keyword) !== -1;
        }

        /*
        | Dựng DOM bằng DOMParser chứ không bằng div.innerHTML: div thường sẽ tải
        | mọi <img> trong nội dung, mà hàm này chạy lại sau mỗi lần gõ phím.
        */
        let parser = new DOMParser();

        function parseHtml(html) {
            return parser.parseFromString(html || '', 'text/html').body;
        }

        function stripHtml(html) {
            return (parseHtml(html).textContent || '').replace(/\s+/g, ' ').trim();
        }

        function editorValue(el) {
            if (!el.length) return '';
            let id = el.attr('id');
            let editor = (id && typeof tinymce !== 'undefined') ? tinymce.get(id) : null;
            if (editor) return editor.getContent();
            return el.val() || '';
        }

        function countWords(text) {
            return text.length ? text.split(/\s+/).filter(Boolean).length : 0;
        }

        function occurrences(text, keyword) {
            if (!keyword.length) return 0;
            let n = 0, pos = 0;
            while ((pos = text.indexOf(keyword, pos)) !== -1) { n++; pos += keyword.length; }
            return n;
        }

        let canvas = document.createElement('canvas').getContext('2d');

        //Google cắt tiêu đề theo độ rộng hiển thị (~580px, Arial 20px), không theo số ký tự
        function textWidth(text, font) {
            canvas.font = font;
            return canvas.measureText(text).width;
        }

        function truncateWidth(text, font, max) {
            if (textWidth(text, font) <= max) return text;
            while (text.length && textWidth(text + '…', font) > max) text = text.slice(0, -1);
            return text.trim() + '…';
        }

        function documentTitle(title) {
            let brand = (config.brand || '').trim();
            title = title.trim();
            if (!brand.length) return title;
            if (!title.length) return brand;
            if (title.toLowerCase().indexOf(brand.toLowerCase()) !== -1) return title;
            return title + (config.separator || ' | ') + brand;
        }

        function changeToSlug(title) {
            let slug = (title || '').toLowerCase();
            slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
            slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
            slug = slug.replace(/í|ì|ỉ|ĩ|ị/gi, 'i');
            slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
            slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
            slug = slug.replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y');
            slug = slug.replace(/đ/gi, 'd');
            slug = slug.replace(/[^a-z0-9\s-]/g, '');
            slug = slug.replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
            return slug;
        }

        function linkHost(href) {
            try { return new URL(href, domain).host.toLowerCase(); } catch (e) { return ''; }
        }

        //------------------------------------------------------------------ đọc form

        function collect() {

            let rawTitle = (fields.title.val() || '').trim();

            let rawSeoTitle = (fields.seoTitle.val() || '').trim();

            let seoTitle = rawSeoTitle.length ? rawSeoTitle : rawTitle;

            let rawDescription = (fields.description.val() || '').trim();

            let description = rawDescription.length ? rawDescription : stripHtml(editorValue(fields.excerpt));

            let slug = (fields.slug.val() || '').trim();

            if (!slug.length) slug = changeToSlug(rawTitle);

            let html = editorValue(fields.content);

            let dom = parseHtml(html);

            let text = stripHtml(html);

            return {
                keyword         : norm(fields.keyword.val()),
                seoTitle        : seoTitle,
                seoTitleFromH1  : !rawSeoTitle.length,
                documentTitle   : documentTitle(seoTitle),
                description     : description,
                descriptionFromExcerpt : !rawDescription.length && description.length > 0,
                slug            : slug,
                url             : (domain || '') + slug,
                html            : html,
                dom             : dom,
                text            : norm(text),
                words           : countWords(text),
                image           : (fields.image.val() || '').trim()
            };
        }

        //------------------------------------------------------------------ chấm

        function evaluate(d) {

            let r = {};

            let kw = d.keyword;

            let noKeyword = [false, 'Chưa đặt từ khóa chính.'];

            let titleNorm = norm(d.seoTitle);

            let titleSource = d.seoTitleFromH1 ? ' (đang lấy từ ô Tiêu đề vì chưa điền Meta title)' : '';

            //Từ khóa
            r.keywordNotUsed = [kw.length > 0];

            if (!kw.length) {
                r.keywordUnique = noKeyword;
            } else if (unique.keyword !== kw || unique.items === null) {
                r.keywordUnique = [true, 'Đang kiểm tra từ khóa trùng...'];
            } else if (unique.items.length) {
                r.keywordUnique = [false, 'Từ khóa đã được dùng cho: ' + unique.items.map(function (t) { return '"' + $('<div>').text(t).html() + '"'; }).join(', ') + '. Hai trang cùng nhắm một từ khóa sẽ tranh thứ hạng của nhau.'];
            } else {
                r.keywordUnique = [true];
            }

            r.keywordInTitle = kw.length ? [has(titleNorm, kw), has(titleNorm, kw) ? null : 'Thêm từ khóa chính vào tiêu đề' + titleSource + '.'] : noKeyword;

            if (!kw.length) {
                r.titleStartWithKeyword = noKeyword;
            } else {
                let pos = titleNorm.indexOf(kw);
                r.titleStartWithKeyword = [pos !== -1 && pos <= titleNorm.length / 2];
            }

            r.keywordInMetaDescription = kw.length ? [has(norm(d.description), kw)] : noKeyword;

            r.keywordInPermalink = kw.length ? [d.slug.indexOf(changeToSlug(kw)) !== -1] : noKeyword;

            if (!kw.length) {
                r.keywordIn10Percent = noKeyword;
                r.keywordInContent = noKeyword;
                r.keywordInSubheadings = noKeyword;
                r.keywordStuffing = noKeyword;
            } else {
                let pos = d.text.indexOf(kw);

                r.keywordInContent = [pos !== -1];

                r.keywordIn10Percent = [pos !== -1 && pos <= Math.max(200, d.text.length * 0.1)];

                let inHeading = false;
                $(d.dom).find('h2,h3,h4,h5,h6').each(function () {
                    if (has(norm($(this).text()), kw)) { inHeading = true; return false; }
                });
                r.keywordInSubheadings = [inHeading];

                /*
                | Mật độ từ khóa không còn là yếu tố xếp hạng: chỉ báo lỗi khi bị
                | NHỒI (hơn 3 lần trên 100 từ, tối thiểu 4 lần) chứ không đòi đạt
                | một mật độ "chuẩn" nào.
                */
                let count = occurrences(d.text, kw);
                let per100 = d.words ? (count / d.words) * 100 : 0;
                let stuffed = count >= 4 && per100 > 3;
                r.keywordStuffing = [!stuffed, 'Từ khóa xuất hiện ' + count + ' lần trong ' + d.words + ' từ (' + per100.toFixed(1) + ' lần/100 từ)' + (stuffed ? ', quá dày. Hãy viết tự nhiên hơn hoặc dùng từ đồng nghĩa.' : '.')];
            }

            //Tiêu đề
            let titleLen = d.documentTitle.length;
            let titleWidth = textWidth(d.documentTitle, '20px Arial');
            let brandNote = (d.documentTitle !== d.seoTitle.trim()) ? ' (đã tính cả tên thương hiệu "' + config.brand + '" hệ thống tự ghép vào)' : '';
            if (titleLen < 30) {
                r.lengthTitle = [false, 'Tiêu đề hiển thị có ' + titleLen + ' ký tự' + brandNote + ', hơi ngắn. Nên từ 30 ký tự trở lên' + titleSource + '.'];
            } else if (titleWidth > 580) {
                r.lengthTitle = [false, 'Tiêu đề hiển thị có ' + titleLen + ' ký tự' + brandNote + ', Google sẽ cắt bớt phần cuối. Hãy rút ngắn' + titleSource + '.'];
            } else {
                r.lengthTitle = [true, 'Tiêu đề hiển thị có ' + titleLen + ' ký tự' + brandNote + ', vừa đủ, không bị cắt.'];
            }

            //Mô tả
            let descLen = d.description.length;
            let descSource = d.descriptionFromExcerpt ? ' (đang lấy từ mô tả ngắn vì chưa điền Meta description)' : '';
            if (descLen === 0) {
                r.lengthMetaDescription = [false, 'Chưa có mô tả. Hãy điền Meta description hoặc mô tả ngắn, nếu không Google sẽ tự trích một đoạn bất kỳ.'];
            } else if (descLen < 110) {
                r.lengthMetaDescription = [false, 'Mô tả có ' + descLen + ' ký tự' + descSource + ', hơi ngắn. Nên từ 110 đến 160 ký tự.'];
            } else if (descLen > 160) {
                r.lengthMetaDescription = [false, 'Mô tả có ' + descLen + ' ký tự' + descSource + ', Google sẽ cắt bớt. Nên từ 110 đến 160 ký tự.'];
            } else {
                r.lengthMetaDescription = [true, 'Mô tả có ' + descLen + ' ký tự' + descSource + ', vừa đủ.'];
            }

            //Đường dẫn: ngắn là tốt, không có ngưỡng tối thiểu
            let urlLen = d.url.replace(/^https?:\/\//, '').length;
            r.lengthPermalink = [urlLen <= 75, 'Đường dẫn có ' + urlLen + ' ký tự' + (urlLen > 75 ? ', khá dài. Hãy rút gọn, bỏ bớt từ thừa.' : ', gọn gàng.')];

            //Nội dung
            let $dom = $(d.dom);

            r.contentNotThin = [d.words >= settings.minWords, 'Nội dung có ' + d.words + ' từ' + (d.words >= settings.minWords ? '.' : ', khá mỏng. Nên có từ ' + settings.minWords + ' từ trở lên với thông tin thật sự hữu ích.')];

            let h1 = $dom.find('h1').length;
            r.noH1InContent = [h1 === 0, h1 ? 'Nội dung có ' + h1 + ' thẻ H1. Giao diện đã in tiêu đề làm H1, hãy đổi các thẻ này thành H2.' : null];

            let isLong = d.words >= settings.longWords;

            let subheadings = $dom.find('h2,h3').length;
            r.contentHasSubheadings = [!isLong || subheadings > 0, isLong ? (subheadings ? 'Nội dung có ' + subheadings + ' tiêu đề phụ H2/H3.' : null) : 'Nội dung ngắn, chưa cần tiêu đề phụ.'];

            let longParagraph = 0;
            $dom.find('p').each(function () {
                if (countWords(stripHtml(this.innerHTML)) > 150) longParagraph++;
            });
            if (d.words === 0) {
                r.contentHasShortParagraphs = [false];
            } else if (longParagraph) {
                r.contentHasShortParagraphs = [false, 'Có ' + longParagraph + ' đoạn văn dài hơn 150 từ. Hãy tách nhỏ để dễ đọc trên điện thoại.'];
            } else {
                r.contentHasShortParagraphs = [true, 'Các đoạn văn đều ngắn gọn, dễ đọc.'];
            }

            let lists = $dom.find('ul,ol,table').length;
            r.contentHasLists = [!isLong || lists > 0, isLong ? null : 'Nội dung ngắn, chưa cần danh sách hoặc bảng.'];

            let internal = 0, external = 0;
            $dom.find('a[href]').each(function () {
                let href = ($(this).attr('href') || '').trim();
                if (!href.length || /^(#|mailto:|tel:|javascript:)/i.test(href)) return;
                let host = linkHost(href);
                if (!host.length) return;
                if (host === domainHost) internal++; else external++;
            });
            r.linksHasInternal = [internal > 0, internal ? 'Nội dung có ' + internal + ' liên kết nội bộ.' : null];
            r.linksHasExternal = [external > 0, external ? 'Nội dung có ' + external + ' liên kết ra website bên ngoài.' : null];

            let images = $dom.find('img');
            let missingAlt = images.filter(function () { return !($(this).attr('alt') || '').trim().length; }).length;
            if (!images.length) {
                r.imagesHaveAlt = [true, 'Nội dung không có hình ảnh.'];
            } else if (missingAlt) {
                r.imagesHaveAlt = [false, missingAlt + '/' + images.length + ' ảnh trong nội dung chưa có alt. Alt nên mô tả đúng nội dung ảnh, không cần nhồi từ khóa.'];
            } else {
                r.imagesHaveAlt = [true, 'Cả ' + images.length + ' ảnh trong nội dung đều có alt.'];
            }

            r.hasFeaturedImage = [d.image.length > 0];

            return r;
        }

        //------------------------------------------------------------------ hiển thị

        function render(d, results) {

            let total = 0, passed = 0;

            criteriaKeys.forEach(function (key) {

                let result = results[key];

                //Tiêu chí do plugin khác thêm mà không có nhánh chấm: không tính
                if (typeof result === 'undefined') return;

                let weight = (typeof weights[key] === 'number') ? weights[key] : 1;

                let ok = result[0] === true;

                total += weight;

                if (ok) passed += weight;

                let message = result[1] || (ok ? (messageSuccess[key] || criteriaLabels[key]) : criteriaLabels[key]);

                let li = seoPanel.find('li[key="'+key+'"]');

                li.toggleClass('test-success', ok).toggleClass('test-fail', !ok);

                li.find('span.icon').html(ok ? icon.success : icon.error);

                li.find('span.txt').html(message);
            });

            let point = total ? Math.round((passed / total) * 100) : 0;

            $('#seo_point').html(point);

            seoPanel.find('.seo-point-score')
                .toggleClass('is-bad', point < 50)
                .toggleClass('is-ok', point >= 50 && point < 80)
                .toggleClass('is-good', point >= 80);

            //Khung xem trước kết quả tìm kiếm
            seoPanel.find('.js_seo_serp_url').text(d.url);
            seoPanel.find('.js_seo_serp_title').text(truncateWidth(d.documentTitle || 'Chưa có tiêu đề', '20px Arial', 580));
            seoPanel.find('.js_seo_serp_desc').text(d.description.length > 160 ? d.description.slice(0, 157).trim() + '…' : (d.description || 'Chưa có mô tả, Google sẽ tự trích một đoạn trong nội dung.'));
        }

        function run() {
            let data = collect();
            render(data, evaluate(data));
            checkUnique(data.keyword);
        }

        //------------------------------------------------------------------ trùng từ khóa

        let uniqueTimer = null;

        function checkUnique(keyword) {

            if (!keyword.length || unique.keyword === keyword) return;

            unique = {keyword: keyword, items: null};

            clearTimeout(uniqueTimer);

            uniqueTimer = setTimeout(function () {

                request.post(ajax, {
                    action  : 'SkdSeo\\Ajax\\Point::duplicate',
                    module  : config.module || '',
                    id      : config.id || 0,
                    keyword : keyword
                }).then(function (response) {

                    if (unique.keyword !== keyword) return;

                    //Lỗi mạng / module không hỗ trợ: coi như không trùng
                    unique.items = (response && response.status === 'success' && response.data && Array.isArray(response.data.items)) ? response.data.items : [];

                    let data = collect();

                    render(data, evaluate(data));
                });
            }, 500);
        }

        //------------------------------------------------------------------ sự kiện

        let runTimer = null;

        function schedule() {
            clearTimeout(runTimer);
            runTimer = setTimeout(run, 300);
        }

        $.each(fields, function (name, el) {
            if (el.length) el.on('input change', schedule);
        });

        /*
        | TinyMCE không bắn sự kiện trên textarea gốc, ô ảnh của trình quản lý file
        | cũng không chắc bắn change: dò thay đổi theo chu kỳ.
        */
        let snapshot = '';

        setInterval(function () {
            let current = editorValue(fields.content) + '\u0000' + editorValue(fields.excerpt) + '\u0000' + (fields.image.val() || '');
            if (current !== snapshot) {
                snapshot = current;
                run();
            }
        }, 2000);

        run();
    });
</script>
