import{d as o,P as i,l as p}from"./entry.d63b9a39.js";import m from"./index.fa2d6236.js";import"./layout-sidebar.vue.180f31cf.js";import"./page-header.1b1819e2.js";import"./use-http-dump.161252c9.js";import"./fetch.a2f95d2b.js";import"./use-inspector.f79ff06a.js";import"./code-snippet.e9f746c9.js";import"./use-profiler.790a894e.js";import"./use-formats.c79ce0bd.js";import"./table-base.31e6f876.js";import"./sentry-exception.20931253.js";import"./use-smtp.6e0c9e5e.js";const A=o({extends:m,setup(){var e,r;{const{$events:t}=p();return(r=(e=t==null?void 0:t.items)==null?void 0:e.value)!=null&&r.length||t.getAll(),{events:t.items,title:"Inspector",type:i.INSPECTOR}}},head(){return{title:`Inspector [${this.events.length}] | Buggregator`}}});export{A as default};