@include('errors.minimal', ['status' => 403] + \App\Support\ErrorPage::meta(403))
