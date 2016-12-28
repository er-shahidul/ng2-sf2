import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import {UserListComponent} from "./user-list/user-list.component";
import {NewUserComponent} from "./new-user/new-user.component";

const routes: Routes = [
  { path: 'list', component: UserListComponent, data: {title: 'User List'} },
  { path: 'new', component: NewUserComponent, data: {title: 'New User'} },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  providers: []
})
export class RoutingModule { }
