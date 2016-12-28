export class NavItem {
  path?: string;
  outlet?: string;
  canActivate?: any[];
  canActivateChild?: any[];
  canDeactivate?: any[];
  children?: NavItem[];
  label: string;
}
