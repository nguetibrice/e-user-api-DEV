<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Services\Contracts\IPaymentMethodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PaymentMethodsController extends Controller
{
    protected IPaymentMethodService $paymentMethodService;

    public function __construct(IPaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }
    //
    public function index()
    {
        $methods = $this->paymentMethodService->getPaymentMethods();
        return Response::success(['payment_methods' => $methods]);
    }

    public function show($id)
    {
        $method = $this->paymentMethodService->findPaymentMethod($id);
        return Response::success(['paymentMethods' => $method]);
    }
    public function create(Request $request)
    {
        $request->validate([
            "country" => "required|max:2",
            "name" => "required",
            "code" => "required|unique:payment_methods,code",
            "min_limit" => "required",
            "max_limit" => "required",
            "currencies" => "required|array",
            "image" => "sometimes",
            "description" => "sometimes",
            "status" => "required|integer",
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $img_name=time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/payment-method'), $img_name);
            $data["image"] = $img_name;
        }
        $data["code"] = strtoupper($data["code"]);
        $data["country"] = strtoupper($data["country"]);
        $payment_method = $this->paymentMethodService->addPaymentMethod($data);
        return Response::success(['payment_method' => $payment_method]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            "country" => "required|max:2",
            "name" => "required",
            "code" => "required",
            "min_limit" => "required",
            "max_limit" => "required",
            "currencies" => "required|array",
            "image" => "sometimes",
            "description" => "sometimes",
            "status" => "required|integer",
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $img_name = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/payment-method'), $img_name);
            $data["image"] = $img_name;
        }
        $data["code"] = strtoupper($data["code"]);
        $data["country"] = strtoupper($data["country"]);
        $payment_method = $this->paymentMethodService->updatePaymentMethod($id, $data);
        return Response::success(['payment_method' => $payment_method]);
    }

    public function delete($id)
    {
        $this->paymentMethodService->deletePaymentMethod($id);
        return Response::success(['message' => "The payment method referred by '$id' was deleted"]);
    }
}
