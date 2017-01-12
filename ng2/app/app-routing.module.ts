import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {PageNotFoundComponent} from "./page-not-found/page-not-found.component";

const routes: Routes = [
  { path: '', children:[
    { path: 'users', loadChildren: './user/user.module#UserModule' },
    { path: 'entity', loadChildren: './crud/crud.module#CrudModule' },
  ] },
  {path: '**', component: PageNotFoundComponent}
];

@NgModule({
  declarations: [
    PageNotFoundComponent
  ],
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
  providers: []
})
export class RoutingModule {
}
