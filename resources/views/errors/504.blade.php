@include('errors.minimal', ['status' => 504] + \App\Support\ErrorPage::meta(504))
