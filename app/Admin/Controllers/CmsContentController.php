<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CmsCategory;
use App\Models\CmsContent;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CmsContentController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('Quản lý bài viết');
            // $content->description('description');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('Chỉnh sửa bài viết');
            // $content->description('description');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('Đăng bài viết mới');
            // $content->description('description');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(CmsContent::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->title('Tên bài viết')->sortable();
            $grid->image('Hình ảnh')->image('', 50);
            $grid->category('Chủ đề')->display(function ($cate) {
                return $cate['title'];
            });
            $grid->status(trans('language.admin.status'))->switch();
            $grid->created_at('Ngày tạo');
            $grid->updated_at('Lần cuối chỉnh sửa');
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
            $grid->disableExport();
            $grid->model()->orderBy('id', 'desc');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(CmsContent::class, function (Form $form) {
            $form->text('title', 'Tên bài viết')->rules('required', ['required' => trans('validation.required')]);
            $arrCate = (new CmsCategory)->listCate();
            $form->select('category_id', 'Danh mục')->options($arrCate)->rules('required');
            $form->image('image', 'Hình ảnh')->uniqueName()->move('cms_content')->removable();
            $form->ckeditor('content', trans('language.admin.content'));
            $form->switch('status', trans('language.admin.status'));
            $form->number('sort', 'Sắp xếp');
            $form->hasMany('images', 'Hình ảnh phụ', function (Form\NestedForm $form) {
                $form->image('image', 'Hình ảnh nhỏ')->uniqueName()->removable();
            });
            $form->divide('Hỗ trợ SEO');
            $form->html('<b>Hỗ trợ SEO</b>');
            $form->tags('keyword', 'Từ khóa');
            $form->textarea('description', 'Mô tả')->rules('max:300', ['max' => trans('validation.max')]);

            $form->saved(function (Form $form) {
                $config = \App\Models\Config::pluck('value', 'key')->all();

                $file_path_admin = config('filesystems.disks.admin.root');
                $id              = $form->model()->id;
                $content         = CmsContent::find($id);
                try {
                    if (!file_exists($file_path_admin . '/thumb/' . $content->image)) {
                        if (!empty($config['watermark'])) {
                            \Image::make($file_path_admin . '/' . $content->image)->insert(public_path('watermark.png'), 'bottom-right', 10, 10)->save($file_path_admin . '/' . $content->image);
                        }
                        //thumbnail
                        $image_thumb = \Image::make($file_path_admin . '/' . $content->image);
                        $image_thumb->resize(250, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $image_thumb->save($file_path_admin . '/thumb/' . $content->image);
                        //end thumb
                    }
                    if (($content->images)) {
                        foreach ($content->images as $key => $image) {
                            if (!file_exists($file_path_admin . '/thumb/' . $image->image)) {
                                if (!empty($config['watermark'])) {
                                    \Image::make($file_path_admin . '/' . $image->image)->insert(public_path('watermark.png'), 'bottom-right', 10, 10)->save($file_path_admin . '/' . $image->image);
                                }
                                //thumbnail
                                $image_thumb = \Image::make($file_path_admin . '/' . $image->image);
                                $image_thumb->resize(250, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                                $image_thumb->save($file_path_admin . '/thumb/' . $image->image);
                                //end thumb
                            }
                        }
                    }

                } catch (\Exception $e) {
                    echo $e->getMessage();
                }

            });
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });
        });
    }

    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('');
            $content->description('');
            $content->body(Admin::show(CmsContent::findOrFail($id), function (Show $show) {
                $show->id('ID');
            }));
        });
    }
}
