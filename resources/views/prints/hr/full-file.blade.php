{{--
  full-file.blade.php
  This view is not rendered directly — PdfGeneratorService::generateMultiPage() renders
  the 4 individual documents sequentially via mPDF AddPage().
  This file exists as a reference/fallback only.
--}}
@extends('prints.layouts.print')
@section('content')
@include('prints.hr.application')
@include('prints.hr.forwarding-letter')
@include('prints.hr.employment-agreement')
@include('prints.hr.checklist')
@endsection
