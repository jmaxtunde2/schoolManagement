@include('errors.minimal', ['status' => 500] + \App\Support\ErrorPage::meta(500))
