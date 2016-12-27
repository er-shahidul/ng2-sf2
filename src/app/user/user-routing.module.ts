import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import {UserListComponent} from "./user-list/user-list.component";
import {NewUserComponent} from "./new-user/new-user.component";

const routes: Routes = [
  { path: 'list', component: UserListComponent },
  { path: 'new', component: NewUserComponent },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  providers: []
})
export class RoutingModule { }
