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
            <label for="">Keyword chính</label>
            <div class="input-group">
                <input name="seo_focus_keyword" type="text" class="form-control" id="seo_focus_keyword" value="{{$focusKeyword}}">
                <span class="input-group-addon"><span id="seo_point">0</span>/100</span>
            </div>
            <p style="margin: 5px 0; color: #ccc;">Chèn từ khóa bạn muốn xếp hạng.</p>
        </div>
        <div class="panel-group seo-panel-group" id="seo-group-panel" role="tablist" aria-multiselectable="true">
            <div class="panel panel-default">
                <div id="seo_panel_base" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="seo_panel_heading_base">
                    <div class="panel-body">
                        <ul>
                            @foreach (SKD_Seo_Point::listCriteria() as $key => $label)
                                <li key="{{$key}}" class="seo-check-{{$key}} test-fail">
                                    <span class="icon"><i class="fal fa-times"></i></span>
                                    <span class="txt">{{$label}}</span>
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
        font-size: 15px;
        line-height: 28px;
        position: relative;
        clear: both;
        color: #5a6065;
        margin-bottom: 10px;
    }
    li span.icon {
        color:#fff;
        width: 25px; height: 25px; line-height: 25px;
        text-align: center;
        display: inline-block;border-radius: 50%;
        margin-right: 5px;
    }
    li.test-fail span.icon {
        background-color: #F29C96;
    }
    li.test-success span.icon {
        background-color: var(--green);
    }
</style>

<script>
    $(function () {

        let icon = {
            'error'   : '<i class="fal fa-times"></i>',
            'success' : '<i class="fal fa-check"></i>'
        };

        let messageError = {
            'keywordInTitle' : 'Thêm Từ khóa chính vào tiêu đề SEO.'
        };

        let messageSuccess = {
            'keywordInTitle' : 'Hurray! Bạn đang sử dụng Focus Keyword trong tiêu đề SEO.',
            'lengthTitle' : 'Tuyệt vời! Tiêu đề của bạn đã có độ dài tối ưu',
            'lengthMetaDescription' : 'Tuyệt vời! Mô tả meta seo của bạn đã có độ dài tối ưu'
        };

        let language = '<?php echo Language::default();?>';

		let seo_focus_keyword = $('#seo_focus_keyword');

		let language_title = $('#'+language+'_title');

		let language_name = $('#'+language+'_name');

		let seo_description = $('#seo_description');

        let keyword = {
            'this'  : seo_focus_keyword,
            'value' : seo_focus_keyword.val(),
        };

        let title = {};
        if(typeof language_title.html() != 'undefined') {
            title = {
                'this'  : language_title,
                'value' : language_title.val(),
            };
        }
        else {
            title = {
                'this'  : language_name,
                'value' : language_name.val(),
            };
        }

        let description = {
            'this'  : seo_description,
            'value' : seo_description.val(),
        };

        let content = $('#'+language+'_content').val();

        let slug = $('#slug').val();

        if(typeof slug === 'undefined' || slug.length === 0) {
            slug = ChangeToSlug(title.value);
        }

        title.value = title.value.toLowerCase();

        keyword.value = keyword.value.toLowerCase();

	    if(typeof description.value == 'string') {
		    description.value = description.value.toLowerCase();
        }

        let seoPanel = $('#seo-general');

        seoRankMathGroup();

        function seoRankMathGroup() {

            let point = 0;

            if(keyword.value.length !== 0) {
                //keywordInTitle
                if(title.value.search(keyword.value) !== -1) {
                    point++;
                    seoRankMathChangeStatus('keywordInTitle', 'success');
                }

                //titleStartWithKeyword
                let beginTitle = title.value.search(keyword.value);

                if(beginTitle === 0) {
                    point++;
                    seoRankMathChangeStatus('titleStartWithKeyword', 'success');
                }
                else {
                    let endTitle = beginTitle + keyword.value.length;

                    if(endTitle === title.value.length) {
                        seoRankMathChangeStatus('titleStartWithKeyword', 'error');
                    }
                    else {
                        point++;
                        seoRankMathChangeStatus('titleStartWithKeyword', 'success');
                    }
                }
            }
            //lengthTitle
            let titleLength = title.value.length;

            if(titleLength >= 10 && titleLength <= 70) {
                point++;
                seoRankMathChangeStatus('lengthTitle', 'success');
            }
            else {
                if(titleLength > 70) mess = 'Tiêu đề có '+ titleLength +' ký tự. Hãy xem xét rút ngắn nó.';
                if(titleLength < 10) mess = 'Tiêu đề '+ titleLength +' ký tự (ngắn). Cố gắng có được 70 ký tự';
                seoPanel.find('li[key="lengthTitle"]').removeClass('test-success').addClass('test-fail');
                seoPanel.find('li[key="lengthTitle"]').find('span.txt').html(mess);
                seoPanel.find('li[key="lengthTitle"]').find('span.icon').html(icon.error);
            }

            if(keyword.value.length !== 0) {
                //keywordInMetaDescription
                if (typeof description.value === 'string' && description.value.search(keyword.value) !== -1) {
                    point++;
                    seoRankMathChangeStatus('keywordInMetaDescription', 'success');
                }
            }

            //lengthMetaDescription
            let descriptionLength = (typeof description.value === 'string') ? description.value.length : 0;

            if(descriptionLength >= 160 && descriptionLength <= 300) {
                point++;
                seoRankMathChangeStatus('lengthMetaDescription', 'success');
            }
            else {
                if(descriptionLength > 300) mess = 'Mô tả meta SEO có '+ descriptionLength +' ký tự. Hãy xem xét rút ngắn nó.';
                if(descriptionLength < 160) mess = 'Mô tả meta SEO có '+ descriptionLength +' ký tự (ngắn). Cố gắng thành 160 ký tự';
                seoPanel.find('li[key="lengthMetaDescription"]').removeClass('test-success').addClass('test-fail');
                seoPanel.find('li[key="lengthMetaDescription"]').find('span.txt').html(mess);
                seoPanel.find('li[key="lengthMetaDescription"]').find('span.icon').html(icon.error);
            }

            if(keyword.value.length !== 0) {
                //keywordInPermalink
                if (slug.search(ChangeToSlug(keyword.value)) !== -1) {
                    point++;
                    seoRankMathChangeStatus('keywordInPermalink', 'success');
                }
            }

            //lengthPermalink
            let object = seoPanel.find('li[key="lengthPermalink"]');
            let slugLength = slug.length + domain.length - 8;
            //Chiều dài url
            if(slugLength > 75 || slugLength < 35) {
                if(slugLength > 75) mess = 'Url có '+ slugLength +' ký tự (dài). Hãy xem xét rút ngắn nó.';
                if(slugLength < 35) mess = 'Url có '+ slugLength +' ký tự (ngắn).';
                object.removeClass('test-success').addClass('test-fail');
                object.find('span.txt').html(mess);
                object.find('span.icon').html(icon.error);
            }
            else {
                point++;
                mess = 'Url có '+ slugLength +' ký tự. Tuyệt vời!';
                object.removeClass('test-fail').addClass('test-success');
                object.find('span.txt').html(mess);
                object.find('span.icon').html(icon.success);
            }

            let contentRemoveHtml = stripHtml(content).toLowerCase();

            if(keyword.value.length !== 0) {
                //keywordIn10Percent & keywordInContent

                let searchKey = contentRemoveHtml.search(keyword.value);

                if (searchKey !== -1) {
                    point++;
                    seoRankMathChangeStatus('keywordInContent', 'success');
                    let firstKeyword = contentRemoveHtml.substr(0, keyword.value.length).toLowerCase();
                    if (keyword.value === firstKeyword) {
                        point++;
                        seoRankMathChangeStatus('keywordIn10Percent', 'success');
                    }
                }
            }

            //lengthContent
            let contentWord = contentRemoveHtml.split(/[\s.,;]+/).length;

            if(contentWord >= 600 && contentWord <= 2500) {
                point++;
                seoRankMathChangeStatus('lengthContent', 'success');
            }
            else {
                seoRankMathChangeStatus('lengthContent', 'error');
            }

            let tmp = document.createElement('div');
            tmp.innerHTML = content;
            //linksHasInternal
            let internalLinks = tmp.getElementsByTagName("a");

            if (internalLinks.length === 0) {
                seoRankMathChangeStatus('linksHasInternal', 'error');
            } else {
                let linksHasInternal = false;
                $.each(internalLinks, function (index, value) {
                    if (internalLinks[index].href.toLowerCase().search(domain) !== -1) {
                        point++;
                        seoRankMathChangeStatus('linksHasInternal', 'success');
                        linksHasInternal = true;
                        return true;
                    }
                });
                if (linksHasInternal === false) {
                    seoRankMathChangeStatus('linksHasInternal', 'error');
                }
            }

            //keywordInSubheadings
            if (keyword.value.length !== 0) {
                let keywordInSubheadings = false;
                let headingH2 = tmp.getElementsByTagName('h2');

                if (headingH2.length !== 0) {
                    $.each(headingH2, function (index, value) {
                        if (headingH2[index].innerText.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInSubheadings', 'success');
                            keywordInSubheadings = true;
                            return true;
                        }
                    });

                }
                let headingH3 = tmp.getElementsByTagName('h3');
                if (keywordInSubheadings === false && headingH3.length !== 0) {
                    $.each(headingH3, function (index, value) {
                        if (headingH3[index].innerText.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInSubheadings', 'success');
                            keywordInSubheadings = true;
                            return true;
                        }
                    });
                }

                let headingH4 = tmp.getElementsByTagName('h4');
                if (keywordInSubheadings === false && headingH4.length !== 0) {
                    $.each(headingH4, function (index, value) {
                        if (headingH4[index].innerText.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInSubheadings', 'success');
                            keywordInSubheadings = true;
                            return true;
                        }
                    });
                }

                let headingH5 = tmp.getElementsByTagName('h5');
                if (keywordInSubheadings === false && headingH5.length !== 0) {
                    $.each(headingH5, function (index, value) {
                        if (headingH5[index].innerText.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInSubheadings', 'success');
                            keywordInSubheadings = true;
                            return true;
                        }
                    });
                }

                let headingH6 = tmp.getElementsByTagName('h5');
                if (keywordInSubheadings === false && headingH6.length !== 0) {
                    $.each(headingH6, function (index, value) {
                        if (headingH6[index].innerText.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInSubheadings', 'success');
                            keywordInSubheadings = true;
                            return true;
                        }
                    });
                }

                if (keywordInSubheadings === false) seoRankMathChangeStatus('keywordInSubheadings', 'error');
            }
            //keywordInImageAlt & contentHasAssets
            let img = tmp.getElementsByTagName('img');

            if (img.length === 0) {
                seoRankMathChangeStatus('keywordInImageAlt', 'error');
                seoRankMathChangeStatus('contentHasAssets', 'error');
            } else {
                if(keyword.value.length !== 0) {
                    let keywordInImageAlt = false;
                    if (img.length >= 2) {
                        point++;
                        seoRankMathChangeStatus('contentHasAssets', 'success');
                    } else {
                        seoRankMathChangeStatus('contentHasAssets', 'error');
                    }
                    $.each(img, function (index, value) {
                        if (img[index].alt.toLowerCase().search(keyword.value) !== -1) {
                            point++;
                            seoRankMathChangeStatus('keywordInImageAlt', 'success');
                            keywordInImageAlt = true;
                            return true;
                        }
                    });
                    if (keywordInImageAlt === false) seoRankMathChangeStatus('keywordInImageAlt', 'error');
                }
            }

            //keywordDensity
            if (keyword.value.length !== 0) {

                object = seoPanel.find('li[key="keywordDensity"]');

                let mess;

                let contentRemoveHtml = stripHtml(content).toLowerCase();

                let contentWord = contentRemoveHtml.split(/[\s.,;]+/).length;

                let nkr = occurrences(contentRemoveHtml, keyword.value);

                let keywordDensity = (nkr / contentWord) * 100;

                keywordDensity = keywordDensity.toFixed(2);

                if(keywordDensity > 2.5 || keywordDensity < 0.75) {
                    if(keywordDensity > 2.5) mess = 'Mật độ từ khóa là '+ keywordDensity +' (cao). Số lần từ khóa xuất hiện là ' +nkr+'.';
                    if(keywordDensity < 0.75) mess = 'Mật độ từ khóa là '+ keywordDensity +' (thấp). Số lần từ khóa xuất hiện là ' +nkr+'.';
                    object.removeClass('test-success').addClass('test-fail');
                    object.find('span.txt').html(mess);
                    object.find('span.icon').html(icon.error);
                }
                else {
                    point++;
                    mess = 'Mật độ từ khóa là '+ keywordDensity +'. Số lần từ khóa xuất hiện là ' +nkr+'.';
                    object.removeClass('test-fail').addClass('test-success');
                    object.find('span.txt').html(mess);
                    object.find('span.icon').html(icon.success);
                }
            }

            //contentHasShortParagraphs
            let tagP = tmp.getElementsByTagName('p');
            if (tagP.length >= 2) {
                point++;
                seoRankMathChangeStatus('contentHasShortParagraphs', 'success');
            } else {
                seoRankMathChangeStatus('contentHasShortParagraphs', 'error');
            }

            if(keyword.value.length === 0) {
                seoRankMathChangeStatus('keywordNotUsed', 'error');
                seoRankMathChangeStatus('keywordInTitle', 'error');
                seoRankMathChangeStatus('titleStartWithKeyword', 'error');
                seoRankMathChangeStatus('keywordInMetaDescription', 'error');
                seoRankMathChangeStatus('keywordInPermalink', 'error');
                seoRankMathChangeStatus('keywordInContent', 'error');
                seoRankMathChangeStatus('keywordIn10Percent', 'error');
                seoRankMathChangeStatus('keywordInImageAlt', 'error');
                seoRankMathChangeStatus('keywordDensity', 'error');
                seoRankMathChangeStatus('keywordInSubheadings', 'error');
            }
            else {
                point++;
                seoRankMathChangeStatus('keywordNotUsed', 'success');
            }

            point = (point/17)*100;

            $('#seo_point').html(Math.ceil(point));
        }

        function seoRankMathChangeStatus(key, status) {
            let object = seoPanel.find('li[key="'+key+'"]');
            if(status === 'success') {
                object.removeClass('test-fail').addClass('test-success');
                object.find('span.txt').html(messageSuccess[key]);
                object.find('span.icon').html(icon.success);
            } else {
                object.removeClass('test-success').addClass('test-fail');
                object.find('span.txt').html(messageError[key]);
                object.find('span.icon').html(icon.error);
            }
        }

        function stripHtml(html) {
            let tmp = document.createElement("DIV");
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || "";
        }

        function ChangeToSlug(title) {
            //Đổi chữ hoa thành chữ thường
            let slug = title.toLowerCase();

            //Đổi ký tự có dấu thành không dấu
            slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
            slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
            slug = slug.replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i');
            slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
            slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
            slug = slug.replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y');
            slug = slug.replace(/đ/gi, 'd');
            //Xóa các ký tự đặt biệt
            slug = slug.replace(/\`|\~|\!|\@|\#|\||\$|\%|\^|\&|\*|\(|\)|\+|\=|\,|\.|\/|\?|\>|\<|\'|\"|\:|\;|_/gi, '');
            //Đổi khoảng trắng thành ký tự gạch ngang
            slug = slug.replace(/ /gi, "-");
            //Đổi nhiều ký tự gạch ngang liên tiếp thành 1 ký tự gạch ngang
            //Phòng trường hợp người nhập vào quá nhiều ký tự trắng
            slug = slug.replace(/\-\-\-\-\-/gi, '-');
            slug = slug.replace(/\-\-\-\-/gi, '-');
            slug = slug.replace(/\-\-\-/gi, '-');
            slug = slug.replace(/\-\-/gi, '-');
            //Xóa các ký tự gạch ngang ở đầu và cuối
            slug = '@' + slug + '@';
            slug = slug.replace(/\@\-|\-\@|\@/gi, '');
            //In slug ra textbox có id “slug”
            return slug;
        }

        function occurrences(string, subString, allowOverlapping) {

            string += "";
            subString += "";
            if (subString.length <= 0) return (string.length + 1);

            var n = 0,
                pos = 0,
                step = allowOverlapping ? 1 : subString.length;

            while (true) {
                pos = string.indexOf(subString, pos);
                if (pos >= 0) {
                    ++n;
                    pos += step;
                } else break;
            }
            return n;
        }

        title.this.change(function () {
            title.value = $(this).val();
            title.value = title.value.toLowerCase();
            if(typeof slug != 'string' && slug.length === 0) {
                slug = ChangeToSlug(title.value);
            }
            seoRankMathGroup();
        });

        description.this.change(function () {
            description.value = $(this).val();
			if(typeof description.value == 'string') {
				description.value = description.value.toLowerCase();
            }
            seoRankMathGroup();
        });

        keyword.this.change(function () {
            keyword.value = $(this).val();
            keyword.value = keyword.value.toLowerCase();
            seoRankMathGroup();
        });

        $(document).on('change', '#slug', function () {
            slug = $('#slug').val();
            seoRankMathGroup();
        });

        $(document).on('change', '#'+language+'_content', function () {
            seoRankMathGroup();
        });

        setInterval(function () {
            if(content !== tinymce.get(language+'_content').getContent()) {
                content = tinymce.get(language+'_content').getContent();
                seoRankMathGroup();
            }
        }, 3000);
    });
</script>