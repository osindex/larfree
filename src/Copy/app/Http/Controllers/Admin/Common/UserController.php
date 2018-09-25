<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Admin\Common;
use App\Models\Common\CommonUser;
use Illuminate\Http\Request;
use AdminApiController as Controller;
class UserController extends Controller
{
    public function __construct(CommonUser $model)
    {
        $this->model = $model;
        parent::__construct();
    }
    public function show($id, Request $request)
    {
        if($id == 0){
            $id = getLoginUserID();
        }
        return parent::show($id, $request); // TODO: Change the autogenerated stub
    }
}