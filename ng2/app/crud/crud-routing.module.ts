import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import {ListEntityComponent} from "./list-entity/list-entity.component";
import {FormEntityComponent} from "./form-entity/form-entity.component";
import {BackendService} from "./backend.service";

const routes: Routes = [
  { path: ':entityName/list', component: ListEntityComponent, data: {title: 'List View'} },
  { path: ':entityName/new', component: FormEntityComponent, data: {title: 'Create New'} },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: [BackendService]
})
export class CrudRoutingModule { }
